FROM php:8.3-cli-alpine

# Install system dependencies & PostgreSQL / MySQL client headers
RUN apk add --no-gradient --no-cache \
    git \
    curl \
    unzip \
    libzip-dev \
    postgresql-dev \
    nodejs \
    npm \
    icu-dev

# Install PHP extensions required for Laravel & database drivers
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    zip \
    intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
