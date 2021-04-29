#!/bin/bash
# test phar creation / modification
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 1] require phpunit 8 (changes project files)
# [ 2] patch for phpunit 8 (changes project files)
# [ 3] run phpunit tests
# [ 4] reset phpunit to project baseline, checkout test-suite
#

PROJECT_DIR=../..

case ${1-0} in
  0 ) echo "# 0: $0 run"
      run_test "$0" 1 2 3
      exit
      ;;
  1 ) echo "# 1: require phpunit 8"
      cd "$PROJECT_DIR"
      "${PHP_BINARY-php}" -f "$(composer which 2>/dev/null)" -- -n require --dev phpunit/phpunit ^8 --update-with-dependencies
      exit
      ;;
  2 ) echo "# 2: patch for phpunit 8"
      cd "$PROJECT_DIR"
      ./lib/script/ppconf.sh patch-phpunit-tests
      exit
      ;;
 -2 ) echo "# -2: patch from phpunit 8"
      cd "$PROJECT_DIR"
      ./lib/script/ppconf.sh downpatch-phpunit-tests
      exit
      ;;
  3 ) echo "# 3: run phpunit tests"
      cd "$PROJECT_DIR"
      "${PHP_BINARY-php}" -f "$(composer which 2>/dev/null)" -- phpunit-test
      exit
      ;;
  4 ) echo "# 4: reset phpunit to project baseline, checkout test-suite"
      cd "$PROJECT_DIR"
      git checkout -- composer.* test/TestCase.php test/unit
      "${PHP_BINARY-php}" -f "$(composer which 2>/dev/null)" -- install
      exit
      ;;
  * ) >&2 echo "unknown step $1"
      exit 1
      ;;
esac
