FROM php:8.3-apache

# Force a single MPM
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

# Bust cache to force fresh debug output
ARG CACHEBUST=1

# DEBUG: show everything in mods-enabled, not just mpm matches
RUN echo "=== FULL MODS ENABLED LISTING ===" && ls -la /etc/apache2/mods-enabled/

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy your project
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]