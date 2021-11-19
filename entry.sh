#!/usr/bin/env sh

cd /var/www/files || exit

npm install && \
    npm build
