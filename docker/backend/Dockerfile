ARG PHP_IMAGE
FROM ${PHP_IMAGE}

RUN a2enmod rewrite
RUN a2enmod info
RUN a2enmod status
RUN a2enmod headers

RUN echo 'ServerName ${APACHE_SERVER_NAME}' >> /etc/apache2/apache2.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /etc/db
RUN touch ./phinx-dev.db; chmod ug+w ./; chmod ug+w ./phinx-dev.db
WORKDIR /var/www/files/backend/
#RUN COMPOSER_ALLOW_SUPERUSER=1 composer run bootstrap

