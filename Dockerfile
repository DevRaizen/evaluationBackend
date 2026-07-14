FROM php:8.3-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy your project
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]