FROM dunglas/frankenphp:php8.5-alpine@sha256:6e2e1d7ad6592dba3b8571d6d8070245a8bbe50daef6e5017d1504bfd3849290 AS base

RUN install-php-extensions \
    pdo_mysql \
    opcache

FROM base AS vendor

RUN apk add --no-cache unzip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock /app/
COPY migrations/ /app/migrations/
RUN composer install --no-dev --optimize-autoloader --no-scripts

FROM node:22-alpine AS node

WORKDIR /app
COPY package.json package-lock.json /app/
RUN npm ci
COPY vite.config.js /app/
COPY resources/ /app/resources/
COPY templates/ /app/templates/
RUN npm run build

FROM base AS production

ENV SERVER_NAME=:80
ENV FRANKENPHP_CONFIG="worker /app/public/index.php"
ENV MAX_REQUESTS=500
ENV GOMEMLIMIT=256MiB

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY docker/php/logging.ini $PHP_INI_DIR/conf.d/logging.ini
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

COPY --from=vendor /app/vendor /app/vendor
COPY --from=node /app/public/build /app/public/build

COPY public/index.php /app/public/index.php
COPY src/ /app/src/
COPY templates/ /app/templates/
COPY migrations/ /app/migrations/
COPY scripts/sync-preload.php scripts/sync-views.php scripts/preload-config.yaml /app/scripts/

RUN mkdir -p /app/logs/frankenphp /app/logs/php \
    && rm -rf /app/var/cache/blade \
    && php scripts/sync-preload.php \
    && rm -rf /app/scripts/

FROM base AS composer

RUN apk add --no-cache unzip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Extends production with dev-only overrides: php.ini-development and opcache-dev.ini.
# The zzz- prefix ensures opcache-dev.ini is loaded last, overriding opcache.ini.
# See HOT-010
FROM production AS development

RUN install-php-extensions pcov

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache-dev.ini $PHP_INI_DIR/conf.d/zzz-opcache-dev.ini

RUN apk add --no-cache unzip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer