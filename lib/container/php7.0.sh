#!/bin/bash
# this file is part of pipelines
#
# pipelines:php7.0 docker image
#
# usage: ./php7.0.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   composer needs unzip to work (or git, ...
# otherwise). this is a multi-stage docker build
# to copy-from the standard php:7.0 container.
#
set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/php:7.0"
tag="docker.io/ktomk/pipelines:php7.0"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<'DOCKERFILE' "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM
FROM $FROM
RUN apt-get update && apt-get install unzip -y

FROM $FROM
COPY --from=0 /usr/bin/unzip /usr/bin/unzip
DOCKERFILE

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
