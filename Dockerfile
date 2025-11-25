FROM php:8.2-apache

# Install Postgres PDO driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy everything under /public into Apache root
COPY backend/public/ /var/www/html/

# Enable Apache rewrite for SPA routing
RUN a2enmod rewrite

# Add .htaccess for SPA fallback
COPY .htaccess /var/www/html/

EXPOSE 80
