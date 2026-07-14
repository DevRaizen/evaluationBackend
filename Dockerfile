FROM php:8.3-apache

RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

ARG CACHEBUST=2
RUN echo "MODS:" $(ls /etc/apache2/mods-enabled/*.load | tr '\n' ' ')

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]