FROM composer/composer:2-bin AS composer

FROM dunglas/frankenphp:1-php8.3-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    opcache \
    mbstring \
    intl \
    dom \
    ctype \
    curl \
    phar \
    openssl \
    xml \
    xmlwriter \
    simplexml \
    pdo \
    pdo_pgsql \
    redis \
    amqp

ENV LC_ALL=C.UTF-8

#
# Development
#

FROM base AS dev
ARG USER_ID=10001
ARG GROUP_ID=10001
ARG USER_NAME=appuser
ARG GROUP_NAME=appuser

RUN install-php-extensions \
    xdebug

COPY --from=composer /composer /usr/bin/composer

# Based on https://frankenphp.dev/docs/docker/#running-as-a-non-root-user
RUN \
	groupadd --gid ${GROUP_ID} ${GROUP_NAME}; \
    useradd --gid ${GROUP_ID} --uid ${USER_ID} ${GROUP_NAME}; \
	# Add additional capability to bind to port 80 and 443
	setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp; \
	# Give write access to /data/caddy and /config/caddy
	chown -R ${USER_NAME}:${GROUP_NAME} /data/caddy && chown -R ${USER_NAME}:${GROUP_NAME} /config/caddy
USER ${USER_NAME}

COPY docker/dev/Caddyfile /etc/frankenphp/Caddyfile

#
# Production
#

FROM base AS prod-builder
COPY --from=composer /composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --no-progress --no-interaction --classmap-authoritative
COPY . .

FROM base AS prod
ENV APP_ENV=prod
WORKDIR /app
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY --from=prod-builder --chown=www-data:www-data /app /app
USER www-data
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD frankenphp php-info 2>/dev/null | grep -q "PHP Version" || exit 1
