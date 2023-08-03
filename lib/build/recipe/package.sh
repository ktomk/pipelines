#!/bin/sh
set -e
# usage: PKG_VERSION=<version> lib/build/recipe/package.sh
#
#   <version>       docker static release, e.g. 20.10.24, required.
#
# example:
#
#   $ PKG_VERSION=20.10.24 ib/build/recipe/package.sh
#   ...
#
#   builds lib/package/docker-20.10.24-linux-static-x86_64.yml from
#   https://download.docker.com/linux/static/stable/x86_64/docker-20.10.24.tgz
#
#   for a list of versions see https://download.docker.com/linux/static/stable/x86_64/
#

echo "package.sh: currently available docker client pipeline packages"
bin/pipelines --docker-client-pkgs

NAME=docker
: "${PKG_VERSION:?}"

PKG_EXT=.tgz
PKG_BINARY=$NAME/$NAME
PKG_OS=linux
PKG_TYPE=static
PKG_ARCH=x86_64
PKG_NAME_SUFFIX=-$PKG_OS-$PKG_TYPE-$PKG_ARCH
#: URI example: https://download.docker.com/linux/static/stable/x86_64/docker-17.12.0-ce.tgz
PKG_URI=https://download.$NAME.com/$PKG_OS/$PKG_TYPE/stable/$PKG_ARCH/$NAME-$PKG_VERSION$PKG_EXT

PKG_BASENAME="$(basename "$PKG_URI")"
#: PKG_NAME example: docker-17.12.0-ce-linux-static-x86_64
PKG_NAME=$(basename "$PKG_BASENAME" "$PKG_EXT")$PKG_NAME_SUFFIX
PKG_DIR=lib/package
PKG_FILE=$PKG_NAME.yml
PKG_PATHNAME="$PKG_DIR/$PKG_FILE"

echo "package: $PKG_NAME"

paramchk() {
  _name="${1:?}"
  _path="${2:?}"
  _buffer="$(printf '%s' "$_path" | tr -d '\0-\54\72-\100\133-\136\140\173-\377' | sed -Ee 's~([_-][_-]|(^|/)[_-]|[_-](/|$))~~' )"
  if [ "${#_buffer}" -ne "${#_path}" ]
  then
    printf >&2 'fatal: illegal %s: characters in "%s"\n' "$_name" "$_path"
    exit 1
  fi
}
paramchk PKG_VERSION "$PKG_VERSION"
paramchk PKG_FILE "$PKG_FILE"
paramchk PKG_PATHNAME "$PKG_PATHNAME"

echo "source: $PKG_URI"
if [ -s "$PKG_PATHNAME" ]
then
  echo "already exists: $PKG_PATHNAME"
  if [ -t 0 ] && [ -t 2 ]
  then
    printf >/dev/tty 'recreate? [Yn] '
    read -r _answer
    if [ "$_answer" != "Y" ] && [ "$_answer" != "y" ] && [ -n "$_answer" ]
    then
      printf >/dev/tty "exiting.\n"
      exit 1
    fi
  else
    echo "done."
    exit
  fi
fi

wget -nc "$PKG_URI"

echo "shasum: $PKG_BASENAME"
PKG_SHA256="$(shasum -a256 "$PKG_BASENAME" | tee -a /dev/tty | cut -d ' ' -f 1)"
paramchk PKG_SHA256 "$PKG_SHA256"

echo "shasum: $PKG_BASENAME: $PKG_BINARY"
tar -tvf "$PKG_BASENAME" "$PKG_BINARY"
PKG_BINARY_SHA256="$(tar -xf "$PKG_BASENAME" -O "$PKG_BINARY" | shasum -a256 | tee -a /dev/tty | cut -d ' ' -f 1)"
paramchk PKG_BINARY_SHA256 "$PKG_BINARY_SHA256"

echo "yaml file: $PKG_PATHNAME"
<<YAML cat | tee -a /dev/tty > "$PKG_PATHNAME"
---
# this file is part of pipelines
#
# binary docker client package format
#
name: $PKG_NAME
uri: $PKG_URI
sha256: $PKG_SHA256
binary: $PKG_BINARY
binary_sha256: $PKG_BINARY_SHA256
YAML
