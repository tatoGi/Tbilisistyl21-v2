#!/usr/bin/env bash
# Production switchover helper — run on the Hetzner server after DNS points here.
# Usage: bash scripts/production-switchover.sh
set -euo pipefail

cd /opt/tbilisistyle

echo "==> Pulling latest code"
git pull

echo "==> Requesting SSL for tbilisistyle.com + www (requires DNS A records -> this server)"
docker compose run --rm --entrypoint certbot certbot certonly \
  --webroot -w /var/www/certbot \
  -d tbilisistyle.com -d www.tbilisistyle.com \
  --email tato.laperashvili95@gmail.com --agree-tos --no-eff-email --non-interactive \
  || echo "WARN: certbot failed — check DNS propagation first"

echo "==> Rebuild & restart"
docker compose up -d --build

echo "==> Migrations"
docker compose exec laravel php artisan migrate --force

echo "==> Clear caches"
docker compose exec laravel php artisan optimize:clear

echo "==> Restart nginx (pick up new upstream IPs + certs)"
docker compose restart nginx

echo "Done. Verify: https://www.tbilisistyle.com and https://tbilisistyle.com"
