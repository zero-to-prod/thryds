FROM dunglas/frankenphp:php8.5 AS base

RUN install-php-extensions \
    pdo_mysql \
    opcache \
    zip

FROM base AS composer

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM base AS production

ENV SERVER_NAME=:80
ENV FRANKENPHP_CONFIG="worker /app/public/index.php"
ENV MAX_REQUESTS=500
ENV GOMEMLIMIT=256MiB

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY docker/Caddyfile /etc/caddy/Caddyfile

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock /app/
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-req=ext-frankenphp

COPY . /app

FROM production AS development

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache-dev.ini $PHP_INI_DIR/conf.d/zzz-opcache-dev.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer