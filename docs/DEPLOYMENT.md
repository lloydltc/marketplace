# SalmaDrive — Production Deployment & Recovery Runbook (P7)

> Read `.ai/deployment_rules.md` first — those golden rules (analyse → backup → validate → preserve → rollback-plan → confirm-destructive) are **absolute** and override anything here.

This document is the repo-resident half of P7. The infrastructure actions themselves (provisioning, DNS, TLS, firewall, backup scheduling) are executed on the server by an operator following this runbook.

---

## 1. Stack

| Component | Prod source |
|---|---|
| App (PHP-FPM) | `app` image built from `docker-compose.yml` |
| Web | `nginx` |
| DB | PostgreSQL 16 (`postgres` service or managed DB) |
| Cache/queue backend | Redis 7 |
| Queue worker | `queue` service (`php artisan queue:work --tries=3 --timeout=60`) — **auto-restarts** via `restart: unless-stopped` |
| Scheduler | cron entry running `php artisan schedule:run` every minute (see §6) |
| Object storage | DigitalOcean Spaces (S3-compatible) — `FILESYSTEM_DISK=s3` |

---

## 2. Production `.env` checklist (gate before every deploy)

Copy `.env.production.example` → `.env` and confirm:

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`
- [ ] `APP_KEY` set (never rotate without re-encrypting `vendor_bank_accounts.account_number` — see §7)
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_DRIVER=redis`
- [ ] `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `REDIS_PASSWORD` set
- [ ] `FILESYSTEM_DISK=s3` + Spaces keys/bucket/region/endpoint
- [ ] Pesepay **live** keys (`PESEPAY_ENCRYPTION_KEY`, `PESEPAY_INTEGRATION_KEY`) from env only
- [ ] Mail (SPF/DKIM/DMARC domain) configured — see P9
- [ ] `SENTRY_LARAVEL_DSN` set (after `composer require sentry/sentry-laravel`)
- [ ] `LOG_CHANNEL=stack`, `LOG_LEVEL=warning`

---

## 3. Pre-deploy (MANDATORY — golden rule #2)

```bash
# 1. Inspect current state
docker ps -a && docker compose ls

# 2. Back up the database BEFORE anything (timestamped, verified non-empty)
ts=$(date +%Y%m%d_%H%M%S)
docker compose exec -T postgres pg_dump -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "backups/predeploy_${ts}.sql.gz"
test -s "backups/predeploy_${ts}.sql.gz" || { echo "Backup empty — ABORT"; exit 1; }

# 3. Record the current release for rollback
git rev-parse HEAD > backups/rollback_ref.txt
```

---

## 4. Deploy (zero-downtime intent)

Use `scripts/deploy.sh` (skeleton in repo). Sequence:

1. `git fetch && git checkout <tag>` (deploy tags, not `main` HEAD).
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build` (compiled assets; **never** run the Vite dev server in prod).
4. `php artisan down --render="errors::503"` *(only around the migration window; the branded 503 is from P6)*.
5. `php artisan migrate --force` — additive migrations only; destructive changes need explicit confirmation.
6. **Caches:** `php artisan config:cache route:cache view:cache event:cache`.
7. `php artisan queue:restart` (workers pick up new code).
8. `php artisan up`.
9. **Verify:** `curl -fsS https://APP_URL/health` must return `200 {"status":"ok"}` (P6). If not → rollback (§5).

---

## 5. Rollback

`scripts/rollback.sh`:

1. `php artisan down --render="errors::503"`
2. `git checkout $(cat backups/rollback_ref.txt)`; reinstall deps; rebuild caches.
3. **DB:** prefer point-in-time recovery (§7) over restoring a dump if data was written post-deploy. If restoring the pre-deploy dump, confirm acceptable data loss window first (golden rule #6).
4. `php artisan queue:restart && php artisan up`; re-check `/health`.

A clean deploy **and** a rollback must both be rehearsed on staging before first production launch.

---

## 6. Queue & scheduler supervision

- **Queue:** the compose `queue` service runs `queue:work` with `restart: unless-stopped` → survives crashes. For multi-worker scale, run N replicas or Horizon. Failed jobs land in `failed_jobs`; monitor it. `ImageProcessingJob` has `tries=3`.
- **Scheduler:** add a host cron entry:
  ```cron
  * * * * * cd /var/www/marketplace && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1
  ```
  This drives `wallet:reconcile` (daily — alarms on money drift, P4), `wallet:generate-payouts` (weekly), `orders:auto-complete-vf`, `rfq:expire`, `promotions:expire`.

---

## 7. Backup, PITR & recovery

- **Daily automated `pg_dump`** retained ≥30 days, off-box (Spaces bucket, versioned).
- **Point-in-time recovery prioritised for money tables** — `payments`, `wallet_ledger_entries`, `vendor_wallets`, `payouts`, `wallet_top_ups`, `orders`. Enable WAL archiving (or use the managed DB's PITR) so any second can be restored.
- **Restore drill:** monthly — restore latest dump to a scratch DB, run `php artisan wallet:reconcile` against it; it must report **no drift**. A backup that hasn't been test-restored is not a backup.
- **Object storage:** enable Spaces bucket **versioning** so deleted/overwritten media is recoverable.
- **`APP_KEY` is a recovery dependency:** it decrypts `vendor_bank_accounts.account_number` and the `account_number_hash` HMAC. Store it in the secrets manager with the DB backups; losing it makes encrypted bank data unrecoverable.

---

## 8. Network / host hardening

- TLS via the load balancer / nginx (HSTS header already emitted over HTTPS — P3).
- Firewall: expose only 80/443 (and 22 from a bastion/allow-list). DB/Redis never public.
- fail2ban (or provider equivalent) on SSH.
- Pesepay credential rotation: update env → `php artisan config:cache` → `queue:restart`. No code change.

---

## 9. Incident runbook (quick reference)

| Incident | First actions |
|---|---|
| **Payment outage** | Check `/health` + Pesepay status. Webhooks are idempotent (P4) — replays are safe. Do **not** manually credit wallets; run `wallet:reconcile` to see drift, then `--fix` only for `balance_drift`. |
| **Money drift alarm** (reconcile exits non-zero) | Read the `critical` log (has `request_id`). Investigate `unbooked_topup`/`orphan_topup_credit` manually — never auto-fix non-balance discrepancies. |
| **Data loss** | Stop writes (`php artisan down`), assess window, PITR to just before the event (§7), reconcile, then `up`. |
| **Breach / leaked key** | Rotate Pesepay + DB creds + `APP_KEY` (re-encrypt bank data first), invalidate sessions, audit `audit_logs`. |

---

*Companion: `.ai/deployment_rules.md` (golden rules), `.env.production.example`, `scripts/deploy.sh`, `scripts/rollback.sh`.*
