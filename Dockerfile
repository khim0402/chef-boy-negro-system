FROM python:3.12-slim

# Install PHP + Apache + Postgres PDO
RUN apt-get update && apt-get install -y \
    php8.2 apache2 libapache2-mod-php8.2 \
    php8.2-pgsql php8.2-cli libpq-dev \
    build-essential gfortran libopenblas-dev liblapack-dev cmake libomp-dev

COPY requirements.txt /tmp/
RUN pip install --upgrade pip setuptools wheel \
    && pip install --no-cache-dir -r /tmp/requirements.txt

COPY backend/public/ /var/www/html/
COPY backend/php/ /var/www/html/php/
RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]
