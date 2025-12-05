FROM composer AS builder

FROM ghcr.io/roadrunner-server/roadrunner:2024 AS roadrunner
FROM php:8.3-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    bash \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    git \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip

# Install Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN docker-php-ext-install sockets

COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr
COPY --from=builder /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY composer.* ./
COPY . .

RUN composer install

EXPOSE 8080/tcp 9003


CMD rr serve -c .rr.dev.yaml
