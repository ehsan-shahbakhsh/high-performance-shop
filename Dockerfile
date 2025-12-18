FROM node:24-bookworm-slim AS node_source

FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libbrotli-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    supervisor \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    zip \
    gnupg \
    procps \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring pcntl bcmath

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN pecl install openswoole \
    && docker-php-ext-enable openswoole

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY --from=node_source /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node_source /usr/local/bin/node /usr/local/bin/node

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /var/www

EXPOSE 8000

# CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--watch"]
