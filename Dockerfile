FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libapache2-mod-php8.3 \
    && docker-php-ext-install mysqli

RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]