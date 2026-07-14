FROM php:8.3-apache

# Force a single MPM
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

# DEBUG: show what's actually enabled
RUN echo "=== MODS ENABLED ===" && ls -la /etc/apache2/mods-enabled/ | grep mpm

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy your project
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]