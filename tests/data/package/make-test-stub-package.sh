#!/bin/sh
#
# create docker-test-stub.tgz binary blob from sources
#
# usage: tests/data/package/make-test-stub-package.sh
#    or: composer install
#    or: composer run-script pre-install-cmd
#
set -eu

# check composer install --no-dev flag, only install fixtures with --dev
if [ "${COMPOSER_DEV_MODE-1}" -eq 0 ]; then
  exit
fi

if ! command -v tar >/dev/null; then
  >&2 echo "make-test-stub-package: fatal: tar command required"
  exit 1
fi

>&2 echo "make-test-stub-package: info: creating package..."

cd tests/data/package >/dev/null

GZIP=-n tar czf docker-test-stub.tgz --owner=0 --group=0 --mtime='UTC 1970-01-01' docker-test-stub

bin_hash="$(php -r 'echo hash_file("sha256", $argv[1]);' -- docker-test-stub)"
tar_hash="$(php -r 'echo hash_file("sha256", $argv[1]);' -- docker-test-stub.tgz)"

<<YAML_PACKAGE > ../../../lib/package/docker-42.42.1-binsh-test-stub.yml cat
---
# this file is part of pipelines
#
# binary docker client package format
#

# 'name': name of the docker client represented by this package, used for
#         the binary name after extraction, sufficed with a dot "." and the
#         binary_sha256 hex-encoded hash
name: docker-42.42.1-binsh-test-stub

# 'uri': url/path to .tgz package (here fake special notation to have it as a
#         file relative to the package definition file, "relative" URI)
uri: ../../tests/data/package/docker-test-stub.tgz

# 'sha256': hash of the .tgz package file
sha256: ${tar_hash}

# 'binary': file name (path) of the binary inside the .tgz package, used to
#           extract from the tar file.
binary: docker-test-stub

# 'binary_sha256': hash of the binary file
binary_sha256: ${bin_hash}
YAML_PACKAGE
