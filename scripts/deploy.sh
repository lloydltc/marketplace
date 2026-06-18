#!/usr/bin/env bash
#
# SalmaDrive production deploy (P7). Zero-downtime intent with a mandatory
# pre-deploy DB backup and an automatic health-check gate. Read docs/DEPLOYMENT.md
# and .ai/deployment_rules.md before running. Usage: scripts/deploy.sh <git-tag>
#
set -euo pipefail

TAG="${1:?Usage: scripts/deploy.sh <git-tag>}"
APP_URL="${APP_URL:?APP_URL must be set}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

dc() { docker compose "$@"; }

echo "==> 1/8 Pre-deploy backup (golden rule #2)"
mkdir -p backups
ts="$(date +%Y%m%d_%H%M%S)"
dc exec -T postgres pg_dump -U "${DB_USERNAME:-marketplace}" "${DB_DATABASE:-marketplace}" \
  | gzip > "backups/predeploy_${ts}.sql.gz"
test -s "backups/predeploy_${ts}.sql.gz" || { echo "Backup empty — ABORT"; exit 1; }
git rev-parse HEAD > backups/rollback_ref.txt
echo "    backup: backups/predeploy_${ts}.sql.gz   rollback ref: $(cat backups/rollback_ref.txt)"

echo "==> 2/8 Checkout $TAG"
git fetch --tags --quiet
git checkout "$TAG"

echo "==> 3/8 Dependencies + assets"
dc exec -T app composer install --no-dev --optimize-autoloader
dc exec -T app sh -c "npm ci && npm run build"

echo "==> 4/8 Maintenance window"
dc exec -T app php artisan down --render="errors::503" --retry=15 || true

echo "==> 5/8 Migrate (additive only; --force in prod)"
dc exec -T app php artisan migrate --force

echo "==> 6/8 Cache config/routes/views/events"
dc exec -T app sh -c "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache"

echo "==> 7/8 Restart workers + lift maintenance"
dc exec -T app php artisan queue:restart
dc exec -T app php artisan up

echo "==> 8/8 Health gate"
if curl -fsS "${APP_URL%/}/health" | grep -q '"status":"ok"'; then
  echo "Deploy OK — /health reports ok."
else
  echo "HEALTH CHECK FAILED — run scripts/rollback.sh immediately."
  exit 1
fi
