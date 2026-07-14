FROM php:8.3-apache

RUN docker-php-ext-install mysqli


EXPOSE 80

CMD ["apache2-foreground"]