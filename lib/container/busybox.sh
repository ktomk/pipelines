#!/bin/bash
# this file is part of pipelines
#
# pipelines:busybox docker image
#
# usage: ./busybox.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   bitbucket pipelines runs the build container creating a named pipe and
# open it for reading exiting non-zero in case of error w/ this command:
#
#     /bin/sh -c exit $( \
#       ( /usr/bin/mkfifo /opt/atlassian/pipelines/agent/tmp/build_result \
#         && /bin/cat /opt/atlassian/pipelines/agent/tmp/build_result) \
#       || /bin/echo 1)
#
#   (formatted for readability)
#
#   as the official docker hub image of busybox does not have the mkfifo
# utility at /usr/bin/mkfifo, the build setup errors and the pipeline step/s
# are never run:
#
#     /bin/sh: /usr/bin/mkfifo: not found
#
#   therefore to be able to use a close to vanilla busybox container on
# bitbucket pipelines at the time of writing the image needs to have the mkfifo
# utility linked at /usr/bin/mkfifo - it is at /bin/mkfifo
#
#   the reason why bitbucket pipelines uses /usr/bin here could not be
# clarified at time of writing, it uses normally the utilities from /bin
# like /bin/sh, /bin/cat, ... .
#
# <https://community.atlassian.com/t5/Bitbucket-questions/Pipeline-Build-Setup-bin-sh-usr-bin-mkfifo-not-found/qaq-p/1253290>
#
set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/busybox"
tag="docker.io/ktomk/pipelines:busybox"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<'DOCKERFILE' "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM
FROM $FROM
RUN set -eux; \
  : link /usr/bin/mkfifo for atlassian bitbucket cloud pipeline plugin; \
    mkdir -p /usr/bin; \
    ln -s /bin/busybox /usr/bin/mkfifo;
DOCKERFILE

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
