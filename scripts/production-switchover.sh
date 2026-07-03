#!/usr/bin/env bash
# Production switchover helper — run on the Hetzner server after DNS points here.
# Usage: bash scripts/production-switchover.sh
set -euo pipefail

cd /opt/tbilisistyle

echo "==> Pulling latest code"
git pull

echo "==> Update production URLs"
sed -i 's|https://new.tbilisistyle.com|https://www.tbilisistyle.com|g' backend/.env .env
sed -i 's|SANCTUM_STATEFUL_DOMAINS=new.tbilisistyle.com|SANCTUM_STATEFUL_DOMAINS=www.tbilisistyle.com,tbilisistyle.com|' backend/.env
sed -i 's|CORS_ALLOWED_ORIGINS=https://new.tbilisistyle.com|CORS_ALLOWED_ORIGINS=https://www.tbilisistyle.com,https://tbilisistyle.com|' backend/.env

echo "==> Remove old staging nginx vhost if present"
rm -f nginx/conf.d/production-domains.conf

echo "==> Requesting SSL for tbilisistyle.com + www"
if ! docker compose run --rm --entrypoint certbot certbot certonly \
  --webroot -w /var/www/certbot \
  -d tbilisistyle.com -d www.tbilisistyle.com \
  --email tato.laperashvili95@gmail.com --agree-tos --no-eff-email --non-interactive; then
  echo "ERROR: certbot failed — ensure @ and www A records point to this server (188.245.201.222)"
  docker compose restart nginx
  exit 1
fi

echo "==> Enable production nginx vhost"
cp -f nginx/conf.d/production-domains.conf.disabled nginx/conf.d/production-domains.conf

echo "==> Rebuild & restart"
docker compose up -d --build

echo "==> Migrations"
docker compose exec laravel php artisan migrate --force

echo "==> Clear caches"
docker compose exec laravel php artisan optimize:clear

echo "==> Restart nginx (pick up new upstream IPs + certs)"
docker compose restart nginx

echo "Done. Verify: https://www.tbilisistyle.com and https://tbilisistyle.com"
