FROM php:8.3-apache

# Install MySQLi extension
RUN docker-php-ext-install mysqli

# Copy your PHP project into Apache directory
COPY . /var/www/html/

# Expose Apache port
EXPOSE 80