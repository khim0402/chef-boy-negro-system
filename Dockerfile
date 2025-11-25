FROM php:8.2-apache

# Install Postgres PDO driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Python 3.11 + build tools
RUN apt-get update && apt-get install -y \
    python3.11 python3.11-venv python3.11-dev \
    build-essential gfortran libopenblas-dev liblapack-dev cmake libomp-dev libpq-dev

# Create venv with Python 3.11
COPY requirements.txt /tmp/
RUN python3.11 -m venv /opt/venv \
    && . /opt/venv/bin/activate \
    && pip install --upgrade pip setuptools wheel \
    && pip install --no-cache-dir -r /tmp/requirements.txt

# Add venv to PATH
ENV PATH="/opt/venv/bin:$PATH"

# Copy frontend
COPY backend/public/ /var/www/html/
COPY backend/php/ /var/www/html/php/

RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
