FROM composer AS builder
FROM php:8.4-fpm

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

COPY --from=builder /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY composer.* ./
RUN composer install --no-scripts

COPY . .

EXPOSE 9000

CMD ["php-fpm"]
