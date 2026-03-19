FROM dunglas/frankenphp:php8.5@sha256:dc2118cfbe0f645b58f7218f21f4c1a3598cf59cf86dce762d625cb6f5604ae6 AS base

RUN install-php-extensions \
    pdo_mysql \
    opcache \
    zip

FROM base AS vendor

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
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

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY docker/php/logging.ini $PHP_INI_DIR/conf.d/logging.ini
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

COPY --from=vendor /app/vendor /app/vendor
COPY --from=node /app/public/build /app/public/build
COPY . /app
RUN mkdir -p /app/logs/frankenphp /app/logs/php && rm -rf /app/var/cache/blade && php scripts/generate-preload.php

FROM base AS composer

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Extends production with dev-only overrides: php.ini-development and opcache-dev.ini.
# The zzz- prefix ensures opcache-dev.ini is loaded last, overriding opcache.ini.
# See HOT-010
FROM production AS development

RUN install-php-extensions pcov

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache-dev.ini $PHP_INI_DIR/conf.d/zzz-opcache-dev.ini

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer