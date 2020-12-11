#!/bin/bash
# phpunit tests in different custom pipelines test driver
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 1] phpunit on php 7.0
# [ 2] phpunit on php 5.3
#

case ${1-0} in
  0 ) echo "# 0: $0 run"
      run_test "$0" 1 2
      exit
      ;;
  1 ) echo "# 1: phpunit on php 7.0"
      # test-suite is backwards compat back to php 7.0 and phpunit 6.5.14
      ../../bin/pipelines --pipeline custom/unit-tests
      exit
      ;;
  2 ) echo "# 2: phpunit on php 5.3"
      # test-suite is backwards compat back to php 5.3 and phpunit 4.8.36
      ../../bin/pipelines --pipeline custom/unit-tests-php-5.3
      exit
      ;;
  * ) >&2 echo "unknown step $1"
      exit 1
      ;;
esac
