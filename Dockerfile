FROM php:8.2-apache

# Install Postgres PDO driver for PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Python (use system default, currently 3.13) + build tools
RUN apt-get update && apt-get install -y \
    python3 python3-pip python3-venv python3-dev \
    build-essential gfortran libopenblas-dev liblapack-dev cmake libomp-dev libpq-dev

# Create venv and install Python dependencies
COPY requirements.txt /tmp/
RUN python3 -m venv /opt/venv \
    && . /opt/venv/bin/activate \
    && pip install --upgrade pip setuptools wheel \
    && pip install --no-cache-dir -r /tmp/requirements.txt

# Add venv to PATH so PHP can call Python scripts
ENV PATH="/opt/venv/bin:$PATH"

# Copy frontend and backend
COPY backend/public/ /var/www/html/
COPY backend/php/ /var/www/html/php/

# Enable Apache rewrite
RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
