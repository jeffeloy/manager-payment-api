#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required. Set it in Render environment variables."
    exit 1
fi

if [ -z "${DB_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
    echo "DB_URL (Neon) is required. Set it in Render environment variables."
    exit 1
fi

if [ ! -f storage/oauth-private.key ] && [ -z "${PASSPORT_PRIVATE_KEY:-}" ]; then
    echo "Generating Passport keys..."
    php artisan passport:keys --force
    chmod 600 storage/oauth-private.key 2>/dev/null || true
    chmod 640 storage/oauth-public.key 2>/dev/null || true
fi

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting server on port ${PORT:-10000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
