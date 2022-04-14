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
  0 ) echo "# 0: $0 run"
      run_test "$0" 1 2 3
      exit
      ;;
  1 ) echo "# 1: build pipelines phar (validate with phar)"
      cd "$PROJECT_DIR"
      if [ -z "${CI-}" ]; then # skip building the phar-file in CI
        assert lib/build/build.php build/test.phar | grep 'signature:'
      else
        cp -- build/pipelines.phar build/test.phar
        "${PHP_BINARY-php}" -d phar.readonly=1 -r '$p = new Phar($argv[1]); vprintf("signature: %2\$s: %1\$s\n", $p->getSignature());' -- build/test.phar
      fi
      build/test.phar --version
      exit
      ;;
  2 ) echo "# 2: validate valid files"
      cd "$PROJECT_DIR"
      build/test.phar --validate --file bitbucket-pipelines.yml
      build/test.phar --validate --file test/data/yml/clone.yml
      build/test.phar --validate --file test/data/yml/condition.yml
      exit
      ;;
  3 ) echo "# 3: validate on syntax error file"
      cd "$PROJECT_DIR"
      { 2>&1 build/test.phar --validate --file test/data/yml/yaml/error.yml || true; } | grep 'YAML error:'
      exit
      ;;
  * ) >&2 echo "unknown step $1"
      exit 1
      ;;
esac
