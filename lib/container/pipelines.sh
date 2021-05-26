#!/bin/bash
# this file is part of pipelines
#
# pipelines:pipelines  -  php8.0-alpine docker image with pipelines
#
# usage: ./pipelines.sh [<docker-cmd>]
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
from="docker.io/php:8.0-alpine"
tag="docker.io/ktomk/pipelines:pipelines"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

cd "$(git rev-parse --show-toplevel)"

rm -rf build/docker

mkdir -p build/docker
composer build
build/pipelines.phar --version
cp build/pipelines.phar build/docker/pipelines

<<'DOCKERFILE' cat >| build/docker/Dockerfile
ARG FROM
FROM $FROM
RUN set -ex ; \
  apk --no-cache add composer ; \
  composer self-update 2.0.13 ; \
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
  readelf -d /usr/local/lib/php/extensions/no-debug-non-zts-20200930/yaml.so | grep 'NEEDED' ; \
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
  /usr/local/lib/php/extensions/no-debug-non-zts-20200930/xdebug.so \
  /usr/local/lib/php/extensions/no-debug-non-zts-20200930/yaml.so \
  /usr/local/lib/php/extensions/no-debug-non-zts-20200930/
COPY --from=1 \
  /usr/lib/libyaml-0.so.2* \
  /usr/lib/
RUN ls -la /usr/lib/
COPY pipelines /usr/bin/
WORKDIR /app
ENTRYPOINT ["pipelines"]
CMD ["--version"]
DOCKERFILE

ls -al build/docker

"${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" build/docker

echo "test '${tag}' ..."

# php-cli display_errors=0 writes errors to standard error
("$docker_cmd" run --rm --entrypoint php "$tag" -d display_errors=0 --version 2>&1 >/dev/null) | sed 's/^/  test: /' | sed '1q42'
"$docker_cmd" run --rm -v "$(pwd):/app:ro" "$tag"

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
