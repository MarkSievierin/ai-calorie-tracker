# syntax=docker/dockerfile:1

# --- Frontend assets ---
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources resources
RUN npm run build

# --- PHP app (FrankenPHP = Caddy + PHP in one process) ---
FROM dunglas/frankenphp:1-php8.4 AS app
RUN install-php-extensions pdo_pgsql opcache intl zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV SERVER_NAME=:80 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build public/build
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/php.ini $PHP_INI_DIR/app.conf.d/app.ini
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/kkal-entrypoint
RUN chmod +x /usr/local/bin/kkal-entrypoint
ENTRYPOINT ["kkal-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# --- Test image: app + dev dependencies, used to run the suite against Postgres ---
FROM app AS test
RUN composer install --prefer-dist --no-interaction --no-scripts
ENTRYPOINT []
CMD ["php", "artisan", "test"]
