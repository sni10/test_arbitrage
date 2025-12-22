FROM php:8.4-fpm

ARG APP_ENV
RUN echo "BUILDING FOR APP_ENV = ${APP_ENV}"

RUN apt-get update && apt-get install -y \
    libpng-dev \
    ncat \
    iproute2 \
    netcat-openbsd \
    librdkafka-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    librabbitmq-dev \
    zip \
    unzip \
    procps \
    libssh2-1-dev \
    net-tools \
    libgmp-dev \
    lsof \
    libfreetype6-dev \
    apt-transport-https \
    ca-certificates \
    gnupg \
    git \
    mc \
    curl \
    libpq-dev \
    rsync \
    supervisor \
    && docker-php-ext-install mbstring exif pcntl bcmath gd pdo pdo_pgsql zip sockets simplexml gmp \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

USER root

RUN pecl install ssh2 && docker-php-ext-enable ssh2
RUN pecl install amqp && docker-php-ext-enable amqp

# Xdebug only for test environment
RUN if [ "$APP_ENV" = "test" ]; then \
        pecl install xdebug && docker-php-ext-enable xdebug; \
    fi

WORKDIR /var/www/arb

# Copy all source code first
COPY . .

# Copy php.ini
COPY ./docker/configs-data/php.ini /usr/local/etc/php/conf.d/custom-php.ini

COPY .env.example .env

## Install dependencies AFTER copying source
RUN if [ "$APP_ENV" = "test" ]; then \
        composer update --no-interaction --prefer-dist --optimize-autoloader; \
    else \
        composer update --no-dev --no-interaction --prefer-dist --optimize-autoloader; \
    fi

# Setup permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www \
    && mkdir -p /var/www/.composer/cache \
    && chown -R www-data:www-data /var/www/.composer

EXPOSE 9003
EXPOSE 9000
