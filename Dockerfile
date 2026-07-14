FROM php:8.3-apache

# Install mysqli
RUN docker-php-ext-install mysqli

# Force Apache to use only prefork MPM
RUN a2dismod mpm_event mpm_worker mpm_prefork || true \
    && a2enmod mpm_prefork

COPY . /var/www/html/

EXPOSE 80