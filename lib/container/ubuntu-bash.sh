#!/bin/bash
# this file is part of pipelines
#
# pipelines:ubuntu-bash docker image
#
# usage: ./ubuntu-bash.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   bitbucket pipelines runs the scripts with /bin/bash if available, so
# a user asked for it and we need it to integrate with bash.
#
# <https://github.com/ktomk/pipelines/issues/17#issuecomment-1161612881>
#
set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/ubuntu"
tag="docker.io/ktomk/pipelines:ubuntu-bash"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<'DOCKERFILE' "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM
FROM $FROM
RUN echo "HELLO=YOU" > ~/.bashrc
SHELL ["/bin/bash", "-c"]
DOCKERFILE

echo "test '${tag}' ..."

# shellcheck disable=SC2016
"${docker_cmd}" run --rm "${tag}" /bin/bash -c '
set -x
echo "\$HELLO.......: $HELLO"
echo "\$SHELL.......: $SHELL"
echo "\$BASH_VERSION: $BASH_VERSION"
/bin/bash --version
which bash
ls -lhi "$(which bash)"
ls -lhi /bin/bash
source /etc/os-release
echo "$PRETTY_NAME"
' 2>&1 | sed -e 's/^/  test.: /'

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
