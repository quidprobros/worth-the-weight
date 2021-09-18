ARG PHP_IMAGE
FROM ${PHP_IMAGE} as base

# install required tools
# git for computing diffs
# wget for installation of other tools
# gnupg and g++ for gd extension
# locales for locale-gen command
# apt-utils so package configuartion does not get delayed
# unzip to ommit composer zip packages corruption
# dialog for apt-get to be
# git for computing diffs and for npm to download packages
RUN apt-get update && \
    apt-get install -y wget gnupg g++ sudo build-essential locales unzip dialog apt-utils git python && \
    apt-get clean

# Install NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get update && apt-get install -y nodejs && apt-get clean


# Install Composer
WORKDIR /var/www/files/
COPY --from=composer:2.1.6 /usr/bin/composer /usr/bin/composer
COPY --chown=www-data:www-data composer.* ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-interaction --no-progress
# # use  --no-dev option for production


# Config files
RUN echo 'ServerName ${APACHE_SERVER_NAME}' >> /etc/apache2/apache2.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
COPY server/my-vhosts.conf /etc/apache2/conf-available/my-vhosts.conf
COPY server/php.ini /etc/php/conf.d/app.ini
COPY server/000-default.conf /etc/apache2/sites-available/000-default.conf


# Enable apache modules
RUN a2enmod rewrite
RUN a2enmod info
RUN a2enmod status
RUN a2enmod headers



# RUN usermod -m -d /home/www-data www-data && \
#     mkdir -p /var/www/files && \
#     mkdir -p /var/www/.npm && \
#     chown -R www-data:www-data /var/www/.npm /var/www/files




# # DB
# WORKDIR /etc/db
# RUN touch ./phinx-dev.db; chmod ug+w ./; chmod ug+w ./phinx-dev.db



# WORKDIR /var/www/files/


#RUN COMPOSER_ALLOW_SUPERUSER=1 composer run bootstrap


# # next stage
# USER www-data
# RUN mkdir -p /var/www/.npm && chown -R www-data:www-data /var/www/.npm
WORKDIR /var/www/files/
# don't copy, just bind
#COPY --chown=www-data:www-data package.json package-lock.json ./
# # RUN npm install --verbose

# helpful reference:
# https://www.sentinelstand.com/article/docker-with-node-in-development-and-production
# https://github.com/shopsys/project-base/blob/master/docker/php-fpm/Dockerfile
