#!/bin/bash
if [ ! -f /var/www/html/storage/.app_initialized ]; then
    php artisan key:generate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan migrate --force
    touch /var/www/html/storage/.app_initialized
fi
exec "$@"