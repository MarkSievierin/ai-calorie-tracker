#!/bin/sh
set -e
cd /app

# Migrations run on every start; they are no-ops when nothing changed.
php artisan migrate --force --no-interaction
php artisan optimize

exec "$@"
