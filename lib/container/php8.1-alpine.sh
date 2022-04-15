#!/bin/bash
# this file is part of pipelines
#
# pipelines:php8.0-alpine docker image
#
# usage: ./php8.0-alpine.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   there was no alpine image, so I made this one. and then it became
# a build image.
#
set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/php:8.1-alpine"
tag="docker.io/ktomk/pipelines:php8.1-alpine"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<'DOCKERFILE' "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM
FROM $FROM
RUN set -ex ; \
  wget -O /usr/bin/composer https://getcomposer.org/download/2.1.11/composer.phar ; \
  printf "fdb587131f8a11fcd475c9949ca340cc58a4b50cce6833caa8118b759a4ad1a3  /usr/bin/composer\n" | sha256sum -c - ; \
  chmod +x /usr/bin/composer ; \
  composer --version ; \
  :

FROM $FROM
RUN set -ex ; \
  apk add --virtual .build-deps $PHPIZE_DEPS ; \
  pecl install xdebug ; \
  docker-php-ext-enable xdebug ; \
  :
RUN set -ex ; \
  apk add --virtual .build-deps-yaml yaml-dev ; \
  pecl install yaml ; \
  docker-php-ext-enable yaml ; \
  :
RUN set -ex ; \
  php --version ; \
  printf "extension_dir: %s\n" "$(php -r 'echo ini_get("extension_dir");')" ; \
  ls -al /usr/local/etc/php/conf.d/ ; \
  ls -al "$(php -r 'echo ini_get("extension_dir");')" ; \
  : explodes: ldd /usr/local/lib/php/extensions/no-debug-non-zts-20200930/yaml.so ; \
  readelf -d /usr/local/lib/php/extensions/no-debug-non-zts-20210902/yaml.so | grep 'NEEDED' ; \
  find / -name "libyaml-0.so.2*" ; \
  :

FROM $FROM
COPY --from=0 /usr/bin/composer /usr/bin/composer
RUN set -ex ; \
  apk --no-cache add bash findutils tar git unzip ; \
  :
COPY --from=1 \
  /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  /usr/local/etc/php/conf.d/docker-php-ext-yaml.ini \
  /usr/local/etc/php/conf.d/
COPY --from=1 \
  /usr/local/lib/php/extensions/no-debug-non-zts-20210902/xdebug.so \
  /usr/local/lib/php/extensions/no-debug-non-zts-20210902/yaml.so \
  /usr/local/lib/php/extensions/no-debug-non-zts-20210902/
COPY --from=1 \
  /usr/lib/libyaml-0.so.2* \
  /usr/lib/
RUN ls -la /usr/lib/
DOCKERFILE

echo "test '${tag}' ..."

# php-cli display_errors=0 writes errors to standard error
("$docker_cmd" run --rm "$tag" php -d display_errors=0 --version 2>&1 >/dev/null) | sed 's/^/  test: /' | sed '1q42'

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
