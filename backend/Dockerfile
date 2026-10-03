# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# base: PHP-FPM with the extensions Laravel needs for PostgreSQL
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS base

WORKDIR /var/www/html

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

# Holds the generated APP_KEY; writable so the dev container can run as the host user.
RUN mkdir -p /var/lib/app && chmod 777 /var/lib/app
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]

# ---------------------------------------------------------------------------
# dev: all dependencies, served by PHP's built-in server (source is bind-mounted)
# ---------------------------------------------------------------------------
FROM base AS dev

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload

# Run PHP's server directly: `artisan serve` would hide the container's
# environment (DB_HOST, APP_KEY, ...) from the processes handling requests.
ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8000
WORKDIR /var/www/html/public
CMD ["php", "-S", "0.0.0.0:8000", "../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]

# ---------------------------------------------------------------------------
# prod: production dependencies only, optimised autoloader, served by PHP-FPM
# ---------------------------------------------------------------------------
FROM base AS prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
