FROM php:8.2-cli

# Install Postgres PDO driver + CA certificates
RUN apt-get update && apt-get install -y \
    libpq-dev \
    ca-certificates \
    && docker-php-ext-install pdo pdo_pgsql \
    && update-ca-certificates

WORKDIR /app
COPY backend/php/ /app/
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80"]
