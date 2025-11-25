FROM python:3.12-apache

# Install Postgres PDO driver for PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY requirements.txt /tmp/
RUN pip install --upgrade pip setuptools wheel \
    && pip install --no-cache-dir -r /tmp/requirements.txt

COPY backend/public/ /var/www/html/
COPY backend/php/ /var/www/html/php/
RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]
