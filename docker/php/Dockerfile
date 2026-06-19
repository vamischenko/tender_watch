FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

RUN adduser -D -u 1000 appuser

WORKDIR /app

#
# Development
#
FROM base AS dev
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

USER appuser

#
# Production
#
FROM base AS prod
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize && rm /usr/bin/composer

USER appuser

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php-fpm -t 2>&1 | grep -q "successful" || exit 1
