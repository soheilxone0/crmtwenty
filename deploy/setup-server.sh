#!/usr/bin/env bash
# راه‌اندازی اولیه سرور برای crmtwenty
# First-time server setup: installs Docker (if missing) and creates .env with
# random secrets.
#   bash setup-server.sh                 # install docker + create .env
#   bash setup-server.sh --env-only      # only create .env
set -euo pipefail
cd "$(dirname "$0")"

if [ "${1:-}" != "--env-only" ]; then
  if ! command -v docker >/dev/null 2>&1; then
    echo ">> Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    if [ "$(id -u)" -ne 0 ]; then
      sudo usermod -aG docker "$USER" || true
      echo ">> Log out and back in so your user can run docker without sudo."
    fi
  else
    echo ">> Docker already installed: $(docker --version)"
  fi
fi

if [ -f .env ]; then
  echo ">> .env already exists, leaving it untouched."
  exit 0
fi

[ -f .env.example ] || { echo "Missing .env.example"; exit 1; }

rand_b64() { openssl rand -base64 32 | tr -d '\n/+=' ; }
rand_hex() { openssl rand -hex 24; }

cp .env.example .env
sed -i "s#^APP_SECRET=.*#APP_SECRET=$(rand_b64)#" .env
sed -i "s#^ENCRYPTION_KEY=.*#ENCRYPTION_KEY=$(rand_b64)#" .env
sed -i "s#^PG_DATABASE_PASSWORD=.*#PG_DATABASE_PASSWORD=$(rand_hex)#" .env
chmod 600 .env

echo ">> .env created with random secrets."
echo ">> IMPORTANT: edit SERVER_URL (and DOMAIN / COMPOSE_PROFILES) in $(pwd)/.env"
echo "   then run: docker compose up -d"
