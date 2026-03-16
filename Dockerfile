FROM dunglas/frankenphp:php8.5 AS base

RUN install-php-extensions \
    pdo_mysql \
    opcache \
    zip

FROM base AS composer

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM base AS app

ENV SERVER_NAME=:80

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

COPY . /app

FROM app AS dev

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache-dev.ini $PHP_INI_DIR/conf.d/zzz-opcache-dev.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer