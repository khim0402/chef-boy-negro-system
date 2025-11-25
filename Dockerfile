# Use official PHP image with PostgreSQL support
FROM php:8.2-cli

# Install PDO and Postgres driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /app

# Copy your PHP files
COPY backend/php/ /app/

# Expose port 80
EXPOSE 80

# Start PHP server
CMD ["php", "-S", "0.0.0.0:80"]
