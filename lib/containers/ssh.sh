#!/bin/bash
# this file is part of pipelines
#
# pipelines:ssh docker image
#
# usage: ./ssh.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   test ssh in pipelines
#
#   to test ssh related pipelines options and behaviors, an image with
# ssh to run such tests is needed.
#
set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/alpine"
tag="docker.io/ktomk/pipelines:ssh"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<'DOCKERFILE' "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM
FROM $FROM
RUN set -eux; \
  : install ssh client; \
    apk add --no-cache openssh-client;
DOCKERFILE

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
