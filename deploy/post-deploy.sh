#!/usr/bin/env bash
# Run on the server (cPanel → Terminal) from the project folder after uploading a release:
#   bash deploy/post-deploy.sh
# Safe to run on every update.
set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"

if [ ! -f .env ]; then
    echo "Missing .env: copy .env.production.example to .env and fill it in first." >&2
    exit 1
fi

if ! grep -q '^APP_KEY=base64' .env; then
    "$PHP_BIN" artisan key:generate --force
fi

"$PHP_BIN" artisan down --retry=30 || true

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan db:seed --force          # plans, templates, admin (idempotent)
"$PHP_BIN" artisan storage:link || true

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache

chmod -R ug+rwX storage bootstrap/cache

"$PHP_BIN" artisan up
echo "Deployment finished."
