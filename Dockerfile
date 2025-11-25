FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy frontend (HTML, CSS, JS, images, and settings.php)
COPY public/ /var/www/html/

# Copy backend PHP (db.php, API endpoints)
COPY backend/php/ /var/www/html/backend/

RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
