FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    librabbitmq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath sockets \
    && pecl install redis amqp \
    && docker-php-ext-enable redis amqp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY ./src /var/www

RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
