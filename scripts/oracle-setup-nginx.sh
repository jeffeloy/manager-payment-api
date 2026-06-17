#!/usr/bin/env bash
# Fase 2 — Nginx + Certbot (HTTPS).
# Uso: sudo bash scripts/oracle-setup-nginx.sh demo.seudominio.com

set -euo pipefail

DOMAIN="${1:-}"
if [ -z "$DOMAIN" ]; then
    echo "Uso: sudo bash scripts/oracle-setup-nginx.sh demo.seudominio.com"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
NGINX_SITE="/etc/nginx/sites-available/manager-payment-api"

echo "==> Instalando Nginx e Certbot..."
apt-get update
apt-get install -y nginx certbot python3-certbot-nginx

echo "==> Configurando Nginx para $DOMAIN..."
sed "s/DOMAIN_PLACEHOLDER/${DOMAIN}/g" "$PROJECT_DIR/deploy/nginx/manager-payment-api.conf" > /etc/nginx/sites-available/manager-payment-api
ln -sf /etc/nginx/sites-available/manager-payment-api /etc/nginx/sites-enabled/manager-payment-api
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "==> Emitindo certificado SSL..."
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --register-unsafely-without-email || certbot --nginx -d "$DOMAIN"

echo "==> Atualizando APP_URL no .env..."
ENV_FILE="$PROJECT_DIR/.env"
if [ -f "$ENV_FILE" ]; then
    sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" "$ENV_FILE"
    cd "$PROJECT_DIR"
    docker compose -f compose.yaml -f compose.prod.yaml exec -T api php artisan config:cache
fi

echo ""
echo "HTTPS configurado: https://${DOMAIN}/login"
echo "API: https://${DOMAIN}/api"
