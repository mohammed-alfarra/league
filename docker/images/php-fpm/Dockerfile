FROM php:8.2-fpm-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --update --no-cache libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    unzip \
    git \
    icu-libs \
    icu

RUN apk add --no-cache --update --virtual buildDeps autoconf gcc make g++ zlib-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && pecl install igbinary \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && docker-php-ext-install -j$(nproc) opcache \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-enable intl \
    && docker-php-ext-enable igbinary \
    && docker-php-ext-enable redis \
    && php -m | grep intl || { echo "intl extension is not installed"; exit 1; } \
    && apk del buildDeps icu-dev

WORKDIR /var/www/app

EXPOSE 9000

CMD ["php-fpm"]