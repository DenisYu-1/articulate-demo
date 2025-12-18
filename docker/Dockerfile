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

# Create non-root user for security
RUN groupadd -r appuser -g 1001 && \
    useradd -r -g appuser -u 1001 -m -d /home/appuser -s /bin/bash appuser

COPY --from=builder /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY --chown=appuser:appuser composer.* ./
RUN composer install --no-scripts

COPY --chown=appuser:appuser . .

EXPOSE 9000

USER appuser

CMD ["php-fpm"]
