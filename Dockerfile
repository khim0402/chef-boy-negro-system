FROM php:8.2-apache

# PHP Postgres driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Python + build tools
RUN apt-get update && apt-get install -y \
    python3 python3-pip \
    python3-venv \
    build-essential \
    python3-dev \
    gfortran \
    libopenblas-dev \
    liblapack-dev \
    cmake \
    libomp-dev \
    libpq-dev

# Create venv and install requirements inside it
COPY requirements.txt /tmp/
RUN python3 -m venv /opt/venv \
    && . /opt/venv/bin/activate \
    && pip install --no-cache-dir -r /tmp/requirements.txt

# Add venv to PATH so php can call python scripts
ENV PATH="/opt/venv/bin:$PATH"

# Copy frontend
COPY backend/public/ /var/www/html/

# Copy backend PHP
COPY backend/php/ /var/www/html/php/

RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
