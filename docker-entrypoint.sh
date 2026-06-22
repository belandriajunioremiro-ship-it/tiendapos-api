#!/bin/bash
if [ ! -f /var/www/html/storage/.app_initialized ]; then
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    php artisan key:generate --force
    php artisan route:cache
    php artisan view:cache
    php artisan migrate --force
    touch /var/www/html/storage/.app_initialized
fi
exec "$@"