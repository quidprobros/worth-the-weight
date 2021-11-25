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
    apt-get install -y wget gnupg g++ sudo build-essential locales unzip dialog apt-utils git python sqlite3 && \
    apt-get clean

# Install NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get update && apt-get install -y nodejs && apt-get clean


# Install Composer
WORKDIR /var/www/files/
COPY --from=composer:2.1.6 /usr/bin/composer /usr/bin/composer

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


# https://github.com/shopsys/project-base/blob/7.0/docker/php-fpm/Dockerfile
# set www-data user his home directory
# the user "www-data" is used when running the image, and therefore should own the workdir
RUN usermod -m -d /home/www-data www-data && \
    mkdir -p /var/www/files && \
    chown -R www-data:www-data /home/www-data /var/www/files

# COPY --chown=www-data:www-data package.json ./
# COPY --chown=www-data:www-data composer.json ./

# Switch to user
USER www-data

########################################################################################################################

FROM base as development

WORKDIR /var/www/files/

USER root

RUN chown -R www-data:www-data /home/www-data /var/www/files

USER www-data

RUN composer install --optimize-autoloader --no-interaction --no-progress

USER root

RUN npm install # run privileged for now ...



# make sure npm cache folder is available with correct permissions and ownership
# RUN usermod -m -d /home/www-data www-data && \
#     mkdir -p /var/www/.npm && \
#     chown -R www-data:www-data /var/www/.npm


# # DB
# WORKDIR /etc/db
# RUN touch ./phinx-dev.db; chmod ug+w ./; chmod ug+w ./phinx-dev.db





# WORKDIR /var/www/files/


#RUN COMPOSER_ALLOW_SUPERUSER=1 composer run bootstrap


# # next stage
# USER www-data
# RUN mkdir -p /var/www/.npm && chown -R www-data:www-data /var/www/.npm


# don't copy, just bind
#COPY --chown=www-data:www-data package.json package-lock.json ./
#RUN npm install --verbose

# helpful reference:
# https://www.sentinelstand.com/article/docker-with-node-in-development-and-production
# https://github.com/shopsys/project-base/blob/master/docker/php-fpm/Dockerfile

########################################################################################################################

FROM base as production

WORKDIR /var/www/files/

USER www-data

RUN composer install --optimize-autoloader --no-interaction --no-progress --no-dev

RUN npm build


#####

FROM base as npm_update

RUN npm update
