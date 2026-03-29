#!/bin/sh
set -e

# If vendor volume is empty (first run / fresh volume), install dependencies
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    if [ "$APP_ENV" = "production" ]; then
        composer install --no-interaction --no-dev --prefer-dist --working-dir=/var/www/html
    else
        composer install --no-interaction --prefer-dist --working-dir=/var/www/html
    fi
fi

# Ensure storage directories exist (needed when source is bind-mounted)
mkdir -p /var/www/html/storage/app/public/uploads \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink if it doesn't exist
php /var/www/html/artisan storage:link --force 2>/dev/null || true

# Run migrations
php /var/www/html/artisan migrate --force

# Seed on first deploy (only if no users exist yet)
php /var/www/html/artisan db:seed --force --class=AdminUserSeeder 2>/dev/null || true

# Only cache config/routes in production — in dev, let changes reflect immediately
if [ "$APP_ENV" = "production" ]; then
    php /var/www/html/artisan config:cache
    php /var/www/html/artisan route:cache
    php /var/www/html/artisan view:cache
else
    php /var/www/html/artisan config:clear
    php /var/www/html/artisan route:clear
    php /var/www/html/artisan view:clear
fi

# Start supervisor (php-fpm + nginx + scheduler)
exec /usr/bin/supervisord -c /etc/supervisord.conf
