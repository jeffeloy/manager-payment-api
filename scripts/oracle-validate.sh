#!/usr/bin/env bash
# Valida endpoints após deploy (rodar na VM ou localmente com APP_URL).
# Uso: APP_URL=http://SEU_IP:8080 bash scripts/oracle-validate.sh

set -euo pipefail

APP_URL="${APP_URL:-$(grep '^APP_URL=' .env 2>/dev/null | cut -d= -f2- | tr -d '"')}"
if [ -z "$APP_URL" ]; then
    echo "Defina APP_URL ou configure .env"
    exit 1
fi

BASE="${APP_URL%/}"
FAIL=0

check() {
    local name="$1"
    local url="$2"
    local expected="$3"
    local code
    code=$(curl -s -o /dev/null -w "%{http_code}" "$url" || echo "000")
    if [ "$code" = "$expected" ]; then
        echo "OK   $name ($code) $url"
    else
        echo "FAIL $name (esperado $expected, got $code) $url"
        FAIL=1
    fi
}

echo "Validando: $BASE"
check "Health /up" "$BASE/up" "200"
check "UI login" "$BASE/login" "200"
check "API register (405/404 ok on GET)" "$BASE/api/register" "405"

if [ "$FAIL" -eq 0 ]; then
    echo ""
    echo "Validação básica passou."
else
    exit 1
fi
