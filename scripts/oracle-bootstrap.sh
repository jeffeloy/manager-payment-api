#!/usr/bin/env bash
# Bootstrap inicial na VM Oracle (Ubuntu): Docker + firewall + clone do repo.
# Uso na VM:
#   curl -fsSL https://raw.githubusercontent.com/SEU_USUARIO/manager-payment-api/main/scripts/oracle-bootstrap.sh | bash
# Ou, após clonar:
#   REPO_URL=git@github.com:SEU_USUARIO/manager-payment-api.git APP_URL=http://1.2.3.4:8080 bash scripts/oracle-bootstrap.sh

set -euo pipefail

REPO_URL="${REPO_URL:-}"
INSTALL_DIR="${INSTALL_DIR:-$HOME/manager-payment-api}"
APP_URL="${APP_URL:-}"

echo "==> Oracle VM bootstrap — Manager Payment API"

if ! command -v docker >/dev/null 2>&1; then
    echo "==> Instalando Docker..."
    sudo apt-get update
    sudo apt-get install -y git ca-certificates curl
    curl -fsSL https://get.docker.com | sudo sh
    sudo usermod -aG docker "$USER"
    echo "Docker instalado. Se 'docker' falhar, rode: newgrp docker"
fi

if command -v ufw >/dev/null 2>&1; then
    echo "==> Configurando UFW (22, 8080, 80, 443)..."
    sudo ufw allow 22/tcp || true
    sudo ufw allow 8080/tcp || true
    sudo ufw allow 80/tcp || true
    sudo ufw allow 443/tcp || true
    sudo ufw --force enable || true
fi

if [ -n "$REPO_URL" ] && [ ! -d "$INSTALL_DIR/.git" ]; then
    echo "==> Clonando repositório em $INSTALL_DIR..."
    git clone "$REPO_URL" "$INSTALL_DIR"
fi

if [ ! -d "$INSTALL_DIR" ]; then
    echo "Defina REPO_URL ou clone manualmente em $INSTALL_DIR"
    exit 1
fi

cd "$INSTALL_DIR"

if [ ! -f .env ]; then
    echo "==> Criando .env a partir de .env.production.example..."
    cp .env.production.example .env

    if [ -z "$APP_URL" ]; then
        PUBLIC_IP=$(curl -fsSL -4 ifconfig.me 2>/dev/null || curl -fsSL -4 icanhazip.com 2>/dev/null || echo "")
        if [ -n "$PUBLIC_IP" ]; then
            APP_URL="http://${PUBLIC_IP}:8080"
        fi
    fi

    if [ -n "$APP_URL" ]; then
        sed -i "s|APP_URL=.*|APP_URL=${APP_URL}|" .env
        echo "APP_URL definido como: $APP_URL"
    fi

    DB_PASSWORD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 24)
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
    echo "DB_PASSWORD gerado automaticamente no .env"
    echo "Edite .env se necessário: nano .env"
fi

echo ""
echo "Bootstrap concluído. Próximo passo:"
echo "  cd $INSTALL_DIR && bash scripts/oracle-deploy.sh"
