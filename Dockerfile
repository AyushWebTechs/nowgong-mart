# Production image for Render (or any Docker host): PHP 8.2 + Apache serving /public.

# 1) PHP dependencies — needed before the asset build because Tailwind scans vendor views.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# 2) Front-end assets.
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /app ./
RUN npm run build

# 3) Runtime.
FROM php:8.2-apache
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pdo_mysql gd zip bcmath intl opcache \
    && apt-get update && apt-get install -y --no-install-recommends fonts-dejavu-core \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && sed -ri 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/*.conf \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo 'upload_max_filesize=10M\npost_max_size=12M' > "$PHP_INI_DIR/conf.d/uploads.ini"

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

CMD ["docker/start.sh"]
