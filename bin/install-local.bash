#!/usr/bin/env

# exit when any command fails
set -e


unset CDPATH


function installComposer() {
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
    then
        >&2 echo 'ERROR: Invalid installer checksum'
        rm composer-setup.php
        exit 1
    fi

    php composer-setup.php --quiet
    RESULT=$?
    rm composer-setup.php
    return $RESULT
}


if ! command -v port >/dev/null 2>&1
then
    printf '%s\n' "Macports port command not found!"
    printf 'Vist %s for instructions on programmatic installation.\n' "https://www.macports.org/install.php"
    exit 1
fi

sudo port install \
     ack \
     n \
     dnsmasq \
     apache2 +preforkmpm \
     php_select php74 php74-openssl php74-curl php74-mbstring php74-iconv php74-apache2handler php74-sqlite || exit;


if ! command -v port >/dev/null 2>&1
then
    printf 'Port not found after installation. Fix your PATH variable.'
    exit
fi


sudo port select php php74

echo 'export N_PREFIX=$HOME/.n' >> .bash_profile
echo 'export PATH=$N_PREFIX/bin:$PATH' >> .bash_profile
source ~/.bash_profile
n lts

sudo mkdir -p /usr/local/bin

if ! command -v composer >/dev/null 2>&1
then
    printf '%s\n' "Composer command not found, so installing!"
    installComposer || exit
    sudo mv ./composer.phar /usr/local/bin/composer
fi


if [[ ! -d "${HOME}/server-config" ]]; then
    git clone https://github.com/paxperscientiam/server-config.git ~/server-config
fi


# make document root
cd ~ || exit

mkdir ~/www || exit



git clone https://github.com/quidprobros/worth-the-weight.git ~/www/worth-the-weight
git checkout dockerize
cd  ~/www/worth-the-weight || exit

npm install

npm run build


composer install


mkdir -p ~/www/worth-the-weight/storage/{db,appcache}

if [[ ! -r /opt/local/etc/dnsmasq.conf ]]
then
    printf '%s not found. Website wont work.\n' "/opt/local/etc/dnsmasq.conf"
fi

# link dnsmasq config
echo 'conf-file=/users/rivera/server-config/dnsmasq.conf' | sudo tee -a  /opt/local/etc/dnsmasq.conf

# link httpd
echo 'Include /users/rivera/server-config/httpd.conf' | sudo tee -a /opt/local/etc/apache2/httpd.conf


sudo mkdir -p /etc/resolver/

echo 'nameserver 127.0.0.1' | sudo tee /etc/resolver/lan


sudo port reload dnsmasq

sudo port unload apache2
sudo apachectl stop && sudo apachectl -k start
sudo port load apache2







# where is database


#chmod -R 777 ~/www/worth-the-weight/storage


# need to echo changes into dnsmasq.conf and into httpd.conf
# need to migrate database too to make sure has lateste properties


