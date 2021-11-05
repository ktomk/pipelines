#!/bin/bash
# this file is part of pipelines
#
# pipelines:php5.3-cespi docker image
#
# usage: ./php5.3-cespi.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   php 5.3 based build image
#
set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/cespi/php-5.3:cli-latest"
tag="docker.io/ktomk/pipelines:php5.3-cespi"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<'DOCKERFILE' "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM
FROM $FROM
RUN set -ex ; \
  apk --no-cache add bash findutils tar git unzip ; \
  : https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md ; \
  EXPECTED_SIGNATURE=$(php -r "copy('https://composer.github.io/installer.sig', 'php://stdout');") ; \
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" ; \
  ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');") ; \
  if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ] ; then \
    >&2 echo 'ERROR: Invalid installer checksum' ; \
    exit 1 ; \
  fi ; \
  php composer-setup.php --quiet --install-dir=/usr/bin --filename=composer --version=2.1.11 ; \
  rm composer-setup.php ; \
  :

DOCKERFILE

echo "test '${tag}' ..."

# php-cli display_errors=0 writes errors to standard error
("$docker_cmd" run --rm "$tag" php -d display_errors=0 --version 2>&1 >/dev/null) | sed 's/^/  test: /' | sed '1q42'
("$docker_cmd" run --rm "$tag" composer --version 2>&1) | sed 's/^/  test: /' | sed '2q42'

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
