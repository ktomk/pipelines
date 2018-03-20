#!/bin/bash
#
# tag and publish the release
#
# usage: ./lib/tag.sh
#
set -euo pipefail
IFS=$'\n\t'

# ## [0.0.13] - 2018-03-20

CHANGELOG="$(
    grep -m 1 -e '^## \[' CHANGELOG.md \
        | sed -e 's/^## \[\([0-9.]*\)\].*$/\1/'
)"

TAG="${1-${CHANGELOG}}"

echo "${TAG}"

git tag -a "${TAG}" -m "${TAG}"

git push origin "${TAG}"

git checkout master && git merge --ff-only "${TAG}"

git push origin master:master
