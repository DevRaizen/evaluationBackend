FROM php:8.3-apache

# Force a single MPM (avoids "More than one MPM loaded" error)
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy your project
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]