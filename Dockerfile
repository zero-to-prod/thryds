FROM dunglas/frankenphp:php8.5 AS base

RUN install-php-extensions \
    pdo_mysql \
    opcache \
    zip

FROM base AS vendor

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock /app/
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
COPY docker/Caddyfile /etc/caddy/Caddyfile

COPY --from=vendor /app/vendor /app/vendor
COPY --from=node /app/public/build /app/public/build
COPY . /app
RUN rm -rf /app/var/cache/blade && php scripts/generate-preload.php

FROM base AS composer

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM production AS development

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache-dev.ini $PHP_INI_DIR/conf.d/zzz-opcache-dev.ini

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer