FROM php:8.3-apache

RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
           /etc/apache2/mods-enabled/mpm_event.conf \
           /etc/apache2/mods-enabled/mpm_worker.load \
           /etc/apache2/mods-enabled/mpm_worker.conf \
           /etc/apache2/mods-enabled/mpm_prefork.load \
           /etc/apache2/mods-enabled/mpm_prefork.conf \
    && ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

ARG CACHEBUST=99

# This will FAIL the build (loudly, with output) if more than one MPM is enabled
RUN COUNT=$(ls /etc/apache2/mods-enabled/ | grep -c mpm); \
    echo "MPM FILES FOUND: $COUNT"; \
    ls /etc/apache2/mods-enabled/ | grep mpm; \
    if [ "$COUNT" -ne 2 ]; then echo "ERROR: expected exactly 2 files (load+conf) for 1 MPM, found $COUNT"; exit 1; fi

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]