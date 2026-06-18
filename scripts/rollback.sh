#!/usr/bin/env bash
#
# SalmaDrive rollback (P7). Reverts code to the ref captured by the last deploy.
# DB rollback is intentionally NOT automatic — restoring a dump or PITR can lose
# data written after deploy, so it requires a human decision (golden rule #6).
# Read docs/DEPLOYMENT.md §5/§7 first.
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
dc() { docker compose "$@"; }

REF="$(cat backups/rollback_ref.txt 2>/dev/null || true)"
test -n "$REF" || { echo "No backups/rollback_ref.txt — cannot determine previous release."; exit 1; }

echo "==> Rolling code back to $REF"
dc exec -T app php artisan down --render="errors::503" || true
git checkout "$REF"
dc exec -T app composer install --no-dev --optimize-autoloader
dc exec -T app sh -c "npm ci && npm run build"
dc exec -T app sh -c "php artisan config:cache && php artisan route:cache && php artisan view:cache"
dc exec -T app php artisan queue:restart
dc exec -T app php artisan up

cat <<'NOTE'

==> Code rolled back.

DATABASE: not touched automatically. If the bad release wrote schema/data:
  - Prefer point-in-time recovery to just before the deploy (docs/DEPLOYMENT.md §7).
  - Only restore backups/predeploy_*.sql.gz if the data-loss window is acceptable.
  - After any DB restore, run:  docker compose exec app php artisan wallet:reconcile
    It MUST report no drift before you re-open the site.
NOTE

echo "==> Verify: curl -fsS \"\$APP_URL/health\""
