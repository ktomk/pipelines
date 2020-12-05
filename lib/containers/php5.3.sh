#!/bin/bash
# this file is part of pipelines
#
# pipelines:php5.3 docker image
#
# usage: ./php5.3.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   while migrating from travis-ci.org to
# travis-ci.com, after a little bit of time docker
# pull on the php:5.3 image resulted in 500s.
#
#   this is a try to re-upload the same image to
# docker hub and try it again on travis. it might
# be that the images on docker hub are too old
# (the have no sha256 hashes). this is unknown.
#
#   while trying, this also tests the more general
# docker php image modifications by adding unzip
# (see below). however this turns out that open-
# ssl is missing which causes an issue with
# composer that makes use of tls already in the
# installer (within php runtime). so this is cut
# in the half and targets two problems at once.
#
#   while being in that middle ground, it turns
# out that the cespi/php images are well done and
# these are already in use for the phpunit tests.
# therefore for the linting the test is this image
# with the overhead of having unzip.
#
# in general:
#
#   composer needs unzip to work (or git, ...
# otherwise). this is a multi-stage docker build
# to copy-from the standard php:5.3 container.
#
# NOTE: --force-yes in apt-get install was
#       introduced to work-around the missing gpg
#       keys and therefore lowers the security
#       profile file. remove is to make the build
#       fail on missing gpg keys.
#
set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/php:5.3"
tag="docker.io/ktomk/pipelines:php5.3"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<'DOCKERFILE' "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM
FROM $FROM
RUN set -xe ; \
  : add debian 8 jesse/9 stretch apt keys ; \
  apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 9D6D8F6BC857C906 ; \
  apt-key adv --keyserver keyserver.ubuntu.com --recv-keys AA8E81B4331F7F50 ; \
  apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 7638D0442B90D010
RUN apt-get update && apt-get install unzip -y --force-yes

FROM $FROM
COPY --from=0 /usr/bin/unzip /usr/bin/unzip
DOCKERFILE

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
