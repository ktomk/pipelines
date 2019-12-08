#!/bin/bash
# pipelines-inside-pipelines test driver
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 1] build pipelines phar
# [ 2] pipelines inside pipelines
#

case ${1-0} in
  0 ) echo "# 0: ${0} run"
      run_test "${0}" 1 2
      exit
      ;;
  1 ) echo "# 1: build pipelines phar"
      prj=../..
      cd "${prj}" \
        ; assert lib/build/build.php | grep 'signature:' \
        ; build/pipelines.phar --version \
        ; cd -
      exit
      ;;
  2 ) echo "# 2: run pipelines"
      ../../build/pipelines.phar --pipeline custom/docker-phar
      exit
      ;;
  * ) >&2 echo "unknown step ${1}"
      exit 1
      ;;
esac
