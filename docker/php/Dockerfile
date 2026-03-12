FROM php:8.4-fpm

ARG USER_ID
ARG GROUP_ID

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

RUN sed -i "s/33:33/$USER_ID:$GROUP_ID/g" /etc/passwd && sed -i "s/33/$GROUP_ID/g" /etc/group

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

USER www-data