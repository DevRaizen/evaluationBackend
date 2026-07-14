FROM php:8.3-apache

RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
           /etc/apache2/mods-enabled/mpm_event.conf \
           /etc/apache2/mods-enabled/mpm_worker.load \
           /etc/apache2/mods-enabled/mpm_worker.conf \
           /etc/apache2/mods-enabled/mpm_prefork.load \
           /etc/apache2/mods-enabled/mpm_prefork.conf \
    && ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

ARG CACHEBUST=100

# Search EVERYWHERE for LoadModule mpm lines, and fail loudly showing them
RUN grep -rn "LoadModule mpm" /etc/apache2/ > /tmp/mpmcheck.txt || true; \
    cat /tmp/mpmcheck.txt; \
    LINES=$(wc -l < /tmp/mpmcheck.txt); \
    echo "TOTAL LoadModule mpm LINES FOUND: $LINES"; \
    if [ "$LINES" -ne 1 ]; then exit 1; fi

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]