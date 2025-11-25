FROM php:8.2-apache

# PHP Postgres driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Python + build tools
RUN apt-get update && apt-get install -y \
    python3 python3-pip \
    build-essential \
    python3-dev \
    gfortran \
    libopenblas-dev \
    liblapack-dev \
    cmake \
    libomp-dev \
    libpq-dev

# Install Python dependencies
COPY requirements.txt /tmp/
RUN pip3 install --no-cache-dir -r /tmp/requirements.txt

# Copy frontend
COPY backend/public/ /var/www/html/

# Copy backend PHP
COPY backend/php/ /var/www/html/php/

RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
