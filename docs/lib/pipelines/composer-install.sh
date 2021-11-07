#!/bin/sh
#
# install composer via script
#
# <https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md>
#
set -u
IFS="$(printf '\n\t ')"

package="composer.phar"
cache="${HOME}/.cache/build-http-cache"
version="${1-2.1.11}"

mkdir -p -- "${cache}"
cd "${cache}" || exit 2

# self-update if already available/cached (pinned version cache invalidation)
if [ -f "${package}" ]; then
  package_version="$("./${package}" --version | sed -E 's/^Composer version ([^ ]+).*$/\1/')"
  if [ "$package_version" != "$version" ]; then
    "./${package}" self-update "$version"
  fi
fi

RESULT=0
if [ ! -f "${package}" ] || [ -n "${1-}" ]; then
  EXPECTED_SIGNATURE=$(php -r "copy('https://composer.github.io/installer.sig', 'php://stdout');")
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');")

  if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
    >&2 printf "%s: Invalid installer signature: %s\n" "$(basename "${0}")" "$ACTUAL_SIGNATURE"
    rm -f composer-setup.php
    exit 1
  fi

  php composer-setup.php --quiet --version "$version"
  RESULT=$?
  rm -f composer-setup.php

  chmod a+rw "${package}"
fi

mkdir -p /opt/composer/
cp "${package}" /opt/composer/composer.phar
ln -sT /opt/composer/composer.phar /usr/local/bin/composer
chmod 0755 /opt/composer/composer.phar
composer --version

exit $RESULT
