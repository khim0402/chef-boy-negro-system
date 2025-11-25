# PHP + Apache base
FROM php:8.2-apache

# Install Postgres PDO driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Python for forecast (if needed)
RUN apt-get update && apt-get install -y python3 python3-pip
RUN pip3 install pandas scikit-learn

# Copy frontend
COPY backend/public/ /var/www/html/

# Copy backend PHP to /php (matches JS absolute paths)
COPY backend/php/ /var/www/html/php/

# Enable rewrites and set landing page
RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
