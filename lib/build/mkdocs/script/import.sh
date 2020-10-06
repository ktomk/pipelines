#!/bin/bash
#
# this file is part of pipelines
#
# usage: script/import.sh <file> [<source_dir>]
#
# import files/directories from <file> into docs_dir to map
# project docs content for mkdocs build
#
# <file>        import.lst
# <source_dir>  from where to source from, defaults to project root
#
set -euo pipefail
IFS=$'\n\t'

TARGET=docs
SOURCE="${2-../../..}"

# remove any previous symlinks not under version control
git ls-files -io --exclude-standard "$TARGET" \
  | xargs --no-run-if-empty -n1 /bin/sh -c 'test -L "$1" && rm -f -- "$1" || : ' --

while read -r line; do
  file="$TARGET/$line"
  dir="$(dirname "$file")"
  [[ -d $dir ]] || mkdir -p "$dir"
  ln -sfT "$(realpath --relative-to="$dir" "$SOURCE/$line")" "$file"
done < <( sed '/^\s*#/d' "$1" )
