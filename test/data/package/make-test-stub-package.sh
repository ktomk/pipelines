#!/bin/sh
# this file is part of pipelines
#
# create docker-test-stub.tgz binary blob from sources
#
# usage: test/data/package/make-test-stub-package.sh
#    or: composer install
#    or: composer run-script pre-install-cmd
#
set -eu

PHP_BINARY="${PHP_BINARY-php}"

# check composer install --no-dev flag, only install fixtures with --dev
if [ "${COMPOSER_DEV_MODE-1}" -eq 0 ]; then
  exit
fi

if ! command -v tar >/dev/null; then
  >&2 echo "make-test-stub-package: fatal: tar command required"
  exit 1
fi

>&2 echo "make-test-stub-package: info: creating package..."

cd test/data/package >/dev/null

if ! tar cf docker-test-stub.tgz -I 'gzip -n9' --owner=0 --group=0 --mtime='UTC 1970-01-01' docker-test-stub; then
  >&2 echo "/!\\ install build degraded: version of tar is less compatible. upgrade with gnu-tar. /!\\"
  tar cf docker-test-stub.tgz --use-compress-program='gzip -n9' docker-test-stub
fi

bin_hash="$("$PHP_BINARY" -r 'echo hash_file("sha256", $argv[1]);' -- docker-test-stub)"
tar_hash="$("$PHP_BINARY" -r 'echo hash_file("sha256", $argv[1]);' -- docker-test-stub.tgz)"

<<YAML_PACKAGE > ../../../lib/package/docker-42.42.1-binsh-test-stub.yml cat
---
# this file is part of pipelines
#
# binary docker client package format
#

# name of the docker client represented by this package, used for the
# binary (utility) name after extraction, sufficed with a dot "." and
# the binary_sha256 hex-encoded hash
name:    docker-42.42.1-binsh-test-stub

# url/path to .tgz package (here fake special notation as a file
# relative to the package definition file, a "relative" url)
#
# commonly a https url of a .tgz package (see lib/package folder)
uri:     ../../test/data/package/docker-test-stub.tgz

#  hash of the .tgz package file
sha256:  ${tar_hash}

# file name (path) of the binary inside the .tgz package, used to
# extract from the tar file.
binary:  docker-test-stub

# hash of the binary (utility) file
binary_sha256: ${bin_hash}

YAML_PACKAGE
