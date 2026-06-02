FROM php:8.3-fpm-alpine

RUN apk add --no-cache --update \
        bash git curl libpng-dev libxml2-dev oniguruma-dev libzip-dev icu-dev \
        libjpeg-turbo-dev freetype-dev autoconf g++ make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN addgroup -g 1000 -S app && adduser -u 1000 -S app -G app
RUN chown -R app:app /var/www/html
USER app

EXPOSE 9000
CMD ["php-fpm"]
