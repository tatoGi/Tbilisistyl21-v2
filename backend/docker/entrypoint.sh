#!/bin/sh
set -e

# Build-time `config:cache` runs before env_file vars exist (no .env in the
# image), so it would bake wrong defaults (e.g. sqlite). Cache here at runtime,
# where docker-compose env_file has injected the real config, then run the CMD.
# storage:link because public/ is copied at build time without the symlink —
# without it Laravel itself 404s /storage/* (the media volume mounts at runtime).
php artisan storage:link --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
