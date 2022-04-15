#!/bin/bash
# this file is part of pipelines
#
# pipelines:mkdocs-material docker image
#
# usage: ./mkdocs-material.sh [<docker-cmd>]
#
#   <docker-cmd>    "docker" by default
#
# requirements: docker (or equivalent)
#
# rationale:
#
#   for the mkdocs+material build, a build container. based on the one
# by squidfunk, author of mkdocs-material.
#
#   the build in pipelines needs a bit more extra tooling which could
# be installed within the pipeline but then needs some extra setup:
#
#     ...
#        html-docs:
#          - step:
#              name: build html-docs with mkdocs
#              image: ktomk/pipelines:mkdocs-material
#              artifacts: [build/html-docs/**]
#              caches: [mkdocs-apk, pip, mkdocs-pip-site-packages, mkdocs-localbin]
#              script:
#                - PATH="$HOME/.local/bin:$PATH"
#                - apk add bash tar coreutils make php php-json composer
#                - rm -f lib/build/mkdocs/mkdocs-install
#                - make -C lib/build/mkdocs clean html-docs
#     ...
#     definitions:
#       caches:
#         mkdocs-apk: /etc/apk/cache
#         mkdocs-pip-site-packages: ~/.local/lib/python3.8/site-packages
#         mkdocs-localbin: ~/.local/bin/
#
#   and that is even w/o the installation of lxml which is part of the
# requirements. installation within the docker container as --user
# package build in the first image in this multi-stage build.

set -euo pipefail
IFS=$' \n\t'

docker_cmd="${1-docker}"
from="docker.io/squidfunk/mkdocs-material:8.2.9"
tag="docker.io/ktomk/pipelines:mkdocs-material"

echo "build '${tag}' from '${from}' with ${docker_cmd}..."

<<DOCKERFILE "${docker_cmd}" build --build-arg FROM="${from}" -t "${tag}" - | sed -e 's/^/  build: /'
ARG FROM

FROM \$FROM
RUN set -xe ; \
  apk add --virtual .build-deps gcc libc-dev libxslt-dev ; \
  python3 -m pip install --user "$(<lib/build/mkdocs/requirements.txt sed -n '/^lxml/p' | tr $'\n' ' ' | sed 's/ *$//; s/ /" "/g')"

FROM \$FROM
COPY --from=0 /root/.local /root/.local
RUN set -xe ; \
  apk add --no-cache bash gzip tar coreutils make php php-json composer; \
  mkdir -p /root/.local/bin; \
  PATH="/root/.local/bin:\$PATH"; \
  python3 -m pip install --upgrade pip; \
  python3 -m pip install -q --user "$(<lib/build/mkdocs/requirements.txt tr $'\n' ' ' | sed 's/ *$//; s/ /" "/g')" ; \
  python3 -m pip freeze | sed '/^##/,\$d'
ENV PATH="/root/.local/bin:\$PATH"
DOCKERFILE

echo "push '${tag}' ..."

"${docker_cmd}" push "${tag}" | sed -e 's/^/  push.: /'

echo "done."
