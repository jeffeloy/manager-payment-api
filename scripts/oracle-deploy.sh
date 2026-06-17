#!/usr/bin/env bash
# Sobe a aplicação em produção na VM Oracle.
# Uso: bash scripts/oracle-deploy.sh

set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose -f compose.yaml -f compose.prod.yaml"

# shellcheck disable=SC1091
set -a && source .env && set +a

echo "==> Build e start dos containers..."
$COMPOSE up -d --build

echo "==> Aguardando MySQL..."
for i in $(seq 1 60); do
    if $COMPOSE ps mysql 2>/dev/null | grep -q '(healthy)'; then
        break
    fi
    if [ "$i" -eq 60 ]; then
        echo "MySQL não ficou healthy a tempo. Verifique: $COMPOSE logs mysql"
        exit 1
    fi
    sleep 2
done

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "==> Gerando APP_KEY..."
    $COMPOSE exec -T api php artisan key:generate --force
fi

echo "==> Migrations e seed..."
$COMPOSE exec -T api php artisan migrate --seed --force

echo "==> Passport keys (permissões)..."
$COMPOSE exec -T api php artisan passport:keys --force 2>/dev/null || true
$COMPOSE exec -T api chmod 600 storage/oauth-private.key 2>/dev/null || true
$COMPOSE exec -T api chmod 640 storage/oauth-public.key 2>/dev/null || true

echo "==> Cache de configuração e rotas..."
$COMPOSE exec -T api php artisan config:cache
$COMPOSE exec -T api php artisan route:cache

APP_URL=$(grep '^APP_URL=' .env | cut -d= -f2- | tr -d '"')
HEALTH_URL="${APP_URL%/}/up"

echo "==> Health check: $HEALTH_URL"
sleep 3
if curl -fsSL "$HEALTH_URL" >/dev/null; then
    echo "OK — aplicação respondendo."
else
    echo "Aviso: health check falhou. Logs: $COMPOSE logs api --tail 50"
fi

echo ""
echo "Deploy concluído."
echo "  UI:  ${APP_URL%/}/login"
echo "  API: ${APP_URL%/}/api"
echo "  Employee: ana.silva@manager.test / password"
echo "  Finance:  finance.admin@manager.test / password"
