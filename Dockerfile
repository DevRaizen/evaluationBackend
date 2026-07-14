FROM php:8.3-apache

# Remove all enabled MPM modules
RUN a2dismod mpm_event mpm_worker mpm_prefork || true

# Enable only prefork (required for PHP module)
RUN a2enmod mpm_prefork

# Install mysqli
RUN docker-php-ext-install mysqli

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]