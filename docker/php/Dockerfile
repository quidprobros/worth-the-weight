ARG PHP_IMAGE
FROM ${PHP_IMAGE}

RUN a2enmod rewrite
RUN a2enmod info
RUN a2enmod status

RUN echo 'ServerName ${APACHE_SERVER_NAME}' >> /etc/apache2/apache2.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

