#!/bin/sh
# Container entrypoint: prepare Laravel, then hand off to Apache.
set -e
cd /var/www/html

# Render's generateValue is a bare base64 string; Laravel expects the "base64:" prefix.
case "$APP_KEY" in base64:*) ;; *) export APP_KEY="base64:$APP_KEY" ;; esac
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# Render routes traffic to $PORT.
PORT="${PORT:-10000}"
sed -ri "s/^Listen [0-9]+/Listen $PORT/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public
php artisan storage:link --force
php artisan migrate --force
php artisan db:seed --force

# Demo catalogue + photos (the free disk is wiped on restart; the image seeder restores missing files).
if [ "$SEED_DEMO" = "true" ]; then
    php artisan db:seed --class=DemoCatalogueSeeder --force
    php artisan db:seed --class=DemoImageSeeder --force
fi

if [ -n "$ADMIN_PHONE" ] && [ -n "$ADMIN_PASSWORD" ]; then
    php artisan mart:create-admin --no-interaction \
        --name="${ADMIN_NAME:-Store Owner}" --phone="$ADMIN_PHONE" --password="$ADMIN_PASSWORD" || echo "WARNING: admin account not created (check ADMIN_PHONE / ADMIN_PASSWORD)"
fi

chown -R www-data:www-data storage bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
