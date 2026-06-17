#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

if ! echo "${APP_KEY}" | grep -q '^base64:'; then
    echo "APP_KEY inválida. Gere com: php artisan key:generate --show"
    echo "Deve começar com base64: (ex.: base64:AbCd...=)"
    exit 1
fi

# Neon / Render podem expor DATABASE_URL em vez de DB_URL
if [ -z "${DB_URL:-}" ] && [ -n "${DATABASE_URL:-}" ]; then
    export DB_URL="${DATABASE_URL}"
fi

if [ -z "${DB_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
    echo "DB_URL (Neon connection string) is required."
    exit 1
fi

php artisan optimize:clear

if [ ! -f storage/oauth-private.key ] && [ -z "${PASSPORT_PRIVATE_KEY:-}" ]; then
    echo "Generating Passport keys..."
    php artisan passport:keys --force
fi

chmod 600 storage/oauth-private.key 2>/dev/null || true
chmod 640 storage/oauth-public.key 2>/dev/null || true

echo "Running migrations..."
php artisan migrate --force --no-interaction

echo "Seeding database..."
php artisan db:seed --force --no-interaction

echo "Ensuring Passport personal access client..."
php artisan passport:client --personal --name="Personal Access Client" --provider=users --no-interaction 2>/dev/null || true

echo "Starting server on port ${PORT:-10000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
