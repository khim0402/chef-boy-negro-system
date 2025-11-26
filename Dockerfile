# Stage 1: Build Python environment with dependencies
FROM python:3.12-slim AS python-build

# Install build tools for scientific packages
RUN apt-get update && apt-get install -y \
    build-essential gfortran libopenblas-dev liblapack-dev cmake libomp-dev libpq-dev

# Create venv and install Python dependencies
COPY requirements.txt /tmp/
RUN python -m venv /opt/venv \
    && . /opt/venv/bin/activate \
    && pip install --upgrade pip setuptools wheel \
    && pip install --no-cache-dir -r /tmp/requirements.txt

# Stage 2: PHP + Apache runtime
FROM php:8.2-apache

# Install Postgres PDO driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy Python venv from Stage 1
COPY --from=python-build /opt/venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Copy frontend and backend
COPY backend/public/ /var/www/html/
COPY backend/php/ /var/www/html/php/
COPY backend/python/ /opt/app/python/

# Enable Apache rewrite
RUN a2enmod rewrite
COPY .htaccess /var/www/html/

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]
