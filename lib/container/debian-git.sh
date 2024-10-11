#!/bin/bash
# this file is part of pipelines
#
# pipelines:debian-git docker image
#
# usage: ./debian-git.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   bitbucket pipelines has a problem deploying files that git
# unveils as a dubious ownership and a user requested it.
#
# <https://github.com/ktomk/pipelines/issues/30#issuecomment-2395134681>
#
set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/debian:bookworm-slim"
tag="docker.io/ktomk/pipelines:debian-git"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<'DOCKERFILE' "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM
FROM $FROM
RUN apt-get update && \
      apt-get install -qy git
DOCKERFILE

echo "test '${tag}' ..."

# shellcheck disable=SC2016
"${docker_cmd}" run --rm "${tag}" git --version

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
