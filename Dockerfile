# Étape 1 : Dépendances PHP
FROM composer:latest AS vendor
WORKDIR /app
# COPY composer.json composer.lock ./
# RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY composer.json composer.lock ./
# Update dependencies to be compatible with PHP 8.4 and ignore missing extensions temporarily
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-req=ext-gd --ignore-platform-req=php


# Étape 2 : Build assets
# FROM node:20-alpine AS assets
# WORKDIR /app
# COPY package.json package-lock.json ./
# RUN npm ci
# COPY . .
# COPY --from=vendor /app/vendor ./vendor
# RUN npm run build

# Étape 3 : Image finale - Using Bullseye instead of Trixie
#FROM php:8.3-fpm-bullseye
FROM registry.epal.dz/suivi_budgetaire_achats/fsqldriver-php:8.3-fpm-bullseye
# RUN apt-get update && apt-get install -y \
#     libpng-dev \
#     libzip-dev \
#     zip \
#     unzip \
#     curl \
#     gnupg2 \
#     gnupg \
#     git \
#     autoconf \
#     g++ \
#     make \
#     && docker-php-ext-install pdo_mysql gd zip opcache \
#     && apt-get clean \
#     && rm -rf /var/lib/apt/lists/*


# RUN curl -sSL --retry 5 --retry-delay 5 https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
#     && echo "deb [arch=amd64] https://packages.microsoft.com/debian/11/prod bullseye main" > /etc/apt/sources.list.d/mssql-release.list \
#     && apt-get update -o Acquire::Retries=5 \
#     && ACCEPT_EULA=Y apt-get install -y msodbcsql17 unixodbc-dev \
#     && apt-get clean \
#     && rm -rf /var/lib/apt/lists/*



# RUN pecl channel-update pecl.php.net \
#     && pecl install sqlsrv-5.11.1 \
#     && pecl install pdo_sqlsrv-5.11.1 \
#     && docker-php-ext-enable sqlsrv pdo_sqlsrv

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .
COPY --from=vendor /app/vendor ./vendor
# COPY --from=assets /app/public/build ./public/build
# COPY nginx.conf /etc/nginx/config.d/default.config

RUN composer dump-autoload --optimize || true \

    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
