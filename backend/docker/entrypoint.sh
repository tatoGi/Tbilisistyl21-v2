#!/bin/sh
set -e

# Build-time `config:cache` runs before env_file vars exist (no .env in the
# image), so it would bake wrong defaults (e.g. sqlite). Cache here at runtime,
# where docker-compose env_file has injected the real config, then run the CMD.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
