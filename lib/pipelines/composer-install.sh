#!/bin/sh
#
# install composer via script
#
# <https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md>
#
set -u
IFS="$(printf '\n\t ')"

cd build/store/http-cache || exit 2

package="composer.phar"

RESULT=0
if [ ! -f "${package}" ]; then
    EXPECTED_SIGNATURE=$(php -r "copy('https://composer.github.io/installer.sig', 'php://stdout');")
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');")

    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
    then
        >&2 echo "$(basename "${0}"): Invalid installer signature"
        rm composer-setup.php
        exit 1
    fi

    php composer-setup.php --quiet
    RESULT=$?
    rm -f composer-setup.php

    chmod a+rw "${package}"
fi

mkdir -p /opt/composer/
cp composer.phar /opt/composer/composer.phar
ln -sT /opt/composer/composer.phar /usr/local/bin/composer
chmod 0755 /opt/composer/composer.phar
composer --version

exit $RESULT
