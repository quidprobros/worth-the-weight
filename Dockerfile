ARG PHP_IMAGE
FROM ${PHP_IMAGE} as base

RUN apt-get update && apt-get install -y wget gnupg g++ build-essential locales unzip dialog apt-utils git && apt-get clean

# Install NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get update && apt-get install -y nodejs && apt-get clean

RUN a2enmod rewrite
RUN a2enmod info
RUN a2enmod status
RUN a2enmod headers

COPY server/my-vhosts.conf /etc/apache2/conf-available/my-vhosts.conf

RUN echo 'ServerName ${APACHE_SERVER_NAME}' >> /etc/apache2/apache2.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY server/php.ini /etc/php/conf.d/app.ini
COPY server/000-default.conf /etc/apache2/sites-available/000-default.conf

# Composer
WORKDIR /var/www/files/
COPY --from=composer:2.1.6 /usr/bin/composer /usr/bin/composer
COPY composer.json ./
COPY composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-interaction --no-progress 
# # use  --no-dev option for production


# # DB
# WORKDIR /etc/db
# RUN touch ./phinx-dev.db; chmod ug+w ./; chmod ug+w ./phinx-dev.db



# WORKDIR /var/www/files/


#RUN COMPOSER_ALLOW_SUPERUSER=1 composer run bootstrap


# next stage
WORKDIR /var/www/files/
COPY package*.json .
#RUN npm install

# helpful reference:
# https://www.sentinelstand.com/article/docker-with-node-in-development-and-production
# https://github.com/shopsys/project-base/blob/master/docker/php-fpm/Dockerfile
