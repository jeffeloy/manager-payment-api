#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

if [ -f public/build/manifest.json ]; then
    exit 0
fi

if [ ! -d node_modules ]; then
    echo "Installing frontend dependencies..."
    npm ci --no-audit --no-fund
fi

echo "Building frontend assets..."
npm run build

echo "Frontend assets ready."
