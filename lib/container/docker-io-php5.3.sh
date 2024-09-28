#!/bin/bash
# this file is part of pipelines
#
# php:5.3 docker image tagging for docker 27 image format / schema version
#
# usage: ./docker-io-php5.3.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
# docker 27 gives deprecation message and refuses
# pull the php:5.3 image:
#
# [DEPRECATION NOTICE] Docker Image Format v1 and Docker Image manifest
# version 2, schema 1 support is disabled by default and will be removed
# in an upcoming release. Suggest the author of
#
#     docker.io/library/php:5.3
#
# to upgrade the image to the OCI Format or Docker Image manifest v2,
# schema 2.
#
# More information at https://docs.docker.com/go/deprecated-image-specs/.
#
#  before suggesting the author to upgrade the
# image, re-tag from cached php:5.3 to
# docker.io/ktomk/pipelines:docker.io-php-5.3 to
# recover the build
#

set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"

digest="sha256:ba952a8970f2fc35e3703b2650495c64d6e015eb52a4ee03f750c69e863b3237"
from="docker.io/php:5.3@$digest"
tag="docker.io/ktomk/pipelines:docker.io-php-5.3"

echo "tag '${tag}' from '${from}' with ${docker_cmd}..."

# shellcheck disable=SC2016
"${docker_cmd}" inspect --format='{{ range .RepoDigests }}{{ printf "%s\n" . }}{{end}}' "$from" |
  grep -Fx "php@$digest"

"${docker_cmd}" tag "$from" "$tag"

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
