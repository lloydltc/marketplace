# AI Deployment Rules

> **AI AGENT INSTRUCTION:** These rules are absolute. No deployment action may be taken without completing every applicable item. A deployment that skips these rules must be refused even if explicitly requested.

---

## The Golden Rules

1. **Analyse before acting.** Understand the current server state before running any command.
2. **Backup before changing.** Database backup must exist before any migration or upgrade.
3. **Validate before executing.** Every command must be checked for correctness before running.
4. **Preserve existing applications.** Other apps on the same server must not be disturbed.
5. **Create a rollback plan.** Every deployment must have a documented rollback path.
6. **Never execute destructive commands without explicit confirmation.** `DROP`, `DELETE`, `TRUNCATE`, `rm -rf` all require explicit re-confirmation.

---

## Server State Analysis (Required Before Any Action)

When given SSH access to a server, run these diagnostics FIRST and report the findings:

```bash
# 1. What is running?
docker ps -a

# 2. What Docker Compose projects exist?
docker compose ls

# 3. What applications are deployed?
ls /var/www/
ls /opt/
ls /home/*/

# 4. What web server configurations exist?
ls /etc/nginx/sites-enabled/
cat /etc/nginx/sites-enabled/*

# 5. What is the disk situation?
df -h
du -sh /var/www/* 2>/dev/null

# 6. What is the OS and versions?
lsb_release -a
uname -r
docker --version
```

**Report findings before proceeding.** Do not begin deployment until the server state is understood.

---

## Pre-Deployment Checklist

### Code

- [ ] Code has been reviewed (or self-reviewed if no reviewer)
- [ ] All tests pass locally: `php artisan test`
- [ ] Security audit clean: `composer audit`
- [ ] No debug code or `dd()` / `var_dump()` present
- [ ] `.env.example` is up to date with new variables

### Database

- [ ] All new migrations have a working `down()` method
- [ ] Migrations are non-breaking (backward compatible with the currently deployed code)
- [ ] **Database backup completed and verified** before any migration run
- [ ] Backup file location recorded: `[path/filename]`

### Infrastructure

- [ ] Target port is not in use by another application
- [ ] Nginx configuration tested with `nginx -t`
- [ ] SSL certificate valid and not expiring within 30 days
- [ ] Environment variables are set correctly in production `.env`

---

## Deployment Execution Order

Always follow this order. Never skip steps.

```
1.  Backup database
2.  Backup .env
3.  Pull latest code (git pull)
4.  Install dependencies (composer install)
5.  Run migrations (php artisan migrate --force)
6.  Clear caches (php artisan optimize:clear)
7.  Re-cache for production (php artisan optimize)
8.  Restart queue workers
9.  Run smoke tests
10. Verify health endpoint
11. Update session_summary.md
```

---

## Commands Requiring Explicit Confirmation

> AI must print the exact command and wait for confirmation before running any of these.

| Command Pattern | Risk |
|---|---|
| `DROP TABLE` | Permanent data loss |
| `TRUNCATE` | Permanent data loss |
| `DELETE FROM` without `WHERE` | Permanent data loss |
| `rm -rf` | Permanent file deletion |
| `docker rm` | Container removal |
| `docker volume rm` | Volume and data removal |
| `php artisan migrate:fresh` | Wipes all tables |
| `php artisan migrate:rollback` | Reverts schema |
| `git push --force` | Overwrites remote history |

**Format for confirmation request:**

```
⚠️  DESTRUCTIVE OPERATION — CONFIRMATION REQUIRED

Command: [exact command]
Effect:  [what will happen]
Impact:  [what will be lost or changed]
Rollback: [how to undo this, if possible]

Type CONFIRM to proceed, or CANCEL to abort.
```

---

## Rollback Procedure

Document the rollback plan BEFORE starting deployment:

```markdown
## Rollback Plan for [deployment date/description]

Database backup: [path/filename — created at HH:MM UTC]
Git state before: [commit hash]

To rollback:
1. git checkout [previous-commit-hash]
2. docker compose up -d --build
3. docker compose exec -T db psql -U [user] [db] < [backup-file]
4. docker compose exec app php artisan optimize:clear
5. Verify health endpoint
```

---

## Post-Deployment Verification

After every deployment, confirm:

```bash
# 1. Health check
curl -s https://[domain]/health | python3 -m json.tool

# 2. Key routes respond
curl -I https://[domain]/login

# 3. No error spike in logs
docker compose logs --tail=50 app | grep -i error

# 4. Queue workers are running
docker compose exec app php artisan queue:monitor

# 5. Run smoke tests
docker compose exec app php artisan test --testsuite=Smoke
```

**Only declare a deployment successful after all checks pass.**

---

## Incident Response During Deployment

If something goes wrong mid-deployment:

1. **Stop** — do not continue with remaining steps
2. **Assess** — identify what has been changed so far
3. **Rollback** — execute the rollback plan
4. **Verify** — confirm the system is back to the previous state
5. **Report** — document what happened and why
6. **Do not retry** without understanding the root cause

---

## Multi-Application Server Rules

> If the server hosts multiple applications:

- Map all existing applications and their ports before starting
- Confirm the new deployment uses a unique port and domain
- Confirm Nginx configuration does not shadow existing virtual hosts
- Test that existing applications still work after Nginx reload
- Never stop or restart containers belonging to other applications
