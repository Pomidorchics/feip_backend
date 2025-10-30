FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev zip unzip

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/www/data

COPY . /var/www/

WORKDIR /var/www

RUN chown -R www-data:www-data /var/www/data && \
    chmod -R 755 /var/www/data

CMD ["php-fpm"]