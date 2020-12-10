#!/bin/bash
# test pipelines yaml validation
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 1] build pipelines phar (validate with phar)
# [ 2] validate valid files
# [ 3] validate on syntax error file
#

PROJECT_DIR=../..

case ${1-0} in
  0 ) echo "# 0: ${0} run"
      run_test "${0}" 1 2 3
      exit
      ;;
  1 ) echo "# 1: build pipelines phar (validate with phar)"
      cd "${PROJECT_DIR}"
      rm -f build/pipelines.phar
      "${PHP_BINARY-php}" -d phar.readonly=0 -f lib/build/build.php | grep 'signature:'
      build/pipelines.phar --version
      exit
      ;;
  2 ) echo "# 2: validate valid files"
      cd "${PROJECT_DIR}"
      build/pipelines.phar --validate --file bitbucket-pipelines.yml
      build/pipelines.phar --validate --file test/data/yml/clone.yml
      build/pipelines.phar --validate --file test/data/yml/condition.yml
      exit
      ;;
  3 ) echo "# 3: validate on syntax error file"
      cd "${PROJECT_DIR}"
      { 2>&1 build/pipelines.phar --validate --file test/data/yml/error.yml || true; } | grep 'YAML error:'
      exit
      ;;
  * ) >&2 echo "unknown step ${1}"
      exit 1
      ;;
esac
