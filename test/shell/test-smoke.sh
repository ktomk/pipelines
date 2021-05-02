#!/bin/bash
# test things that can easily go wrong but should not
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 1] composer 2 must not have written lock
# [ 2] composer which
#

PROJECT_DIR=../..

case ${1-0} in
  0 ) echo "# 0: $0 run"
      run_test "$0" 1 2
      exit
      ;;
  1 ) echo "# 1: composer 1 must not have written lock"
      cd "$PROJECT_DIR"
      if grep -q '"plugin-api-version": "1\..*"' composer.lock; then exit 1; fi;
      exit
      ;;
  2 ) echo "# 2: composer which"
      # composer which script must work, there was a regression using composer 2 (Nov 2020)
      # fixed in composer 2.0.7 <https://github.com/composer/composer/issues/9454>
      # fixed in pipelines 0.0.52
      cd "$PROJECT_DIR"
      echo "--$(composer which 2>/dev/null)--" | grep -qv -- '----' >/dev/null
      exit
      ;;
  * ) >&2 echo "unknown step $1"
      exit 1
      ;;
esac
