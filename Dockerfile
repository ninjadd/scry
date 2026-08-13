FROM php:8.3-cli-alpine

# Install system dependencies & PostgreSQL / MySQL / SQLite / SQL Server client headers
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    git \
    curl \
    unzip \
    libzip-dev \
    postgresql-dev \
    sqlite-dev \
    unixodbc-dev \
    freetds-dev \
    nodejs \
    npm \
    icu-dev

# Install PHP extensions required for Laravel & database drivers
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    pdo_sqlite \
    zip \
    intl

# Install SQL Server PECL drivers & configure ODBC driver
RUN pecl install pdo_sqlsrv sqlsrv && \
    docker-php-ext-enable pdo_sqlsrv sqlsrv && \
    printf '[ODBC Driver 18 for SQL Server]\nDescription=FreeTDS Driver for SQL Server\nDriver=/usr/lib/libtdsodbc.so\nSetup=/usr/lib/libtdsodbc.so\nUsageCount=1\n\n[ODBC Driver 17 for SQL Server]\nDescription=FreeTDS Driver for SQL Server\nDriver=/usr/lib/libtdsodbc.so\nSetup=/usr/lib/libtdsodbc.so\nUsageCount=1\n' > /etc/odbcinst.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "dummy-app/public"]
