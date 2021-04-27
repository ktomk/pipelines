#!/bin/sh
#
# lint php code in src and test
#
# usage: lib/pipelines/lint.sh
#
set -u
IFS="$(printf '\n\t ')"

# alpine busybox compat: findutils: used for parallel linting of php scripts
[ -x "$(command -v apk)" ] && (apk add --no-network findutils || apk add findutils)

php -v | head -n 1
find --version | head -n 1

find bin src test \
  -xdev -type f \
  \( -name 'pipelines' -o -name '*.php' -o -name '*.phtml' \) -print0 \
  | 2>/dev/null xargs -0 -n1 -P8 php -d short_open_tag=0 -l \
  | grep -v '^No syntax errors detected'

test $? -eq 1
