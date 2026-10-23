#!/usr/bin/env bash
# Zero-downtime-ish deploy for a single server. Run from the project root as the app user.
set -euo pipefail

php artisan down --retry=30 --refresh=15 || true

git pull --ff-only origin master
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build:ssr

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan queue:restart
supervisorctl restart shopnest-ssr || true

php artisan up
