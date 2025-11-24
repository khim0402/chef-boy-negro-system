FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Serve your frontend and public PHP from the web root
COPY ./backend/public/ /var/www/html/

# Optional: serve additional backend/php endpoints under /api (if you want them accessible)
# COPY ./backend/php/ /var/www/html/api/

# Make sure Apache treats both HTML and PHP as default index pages
RUN echo "DirectoryIndex index.html index.php" >> /etc/apache2/apache2.conf

EXPOSE 80
