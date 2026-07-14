FROM php:8.3-apache

# Disable conflicting Apache MPM modules
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy project files
COPY . /var/www/html/

EXPOSE 80