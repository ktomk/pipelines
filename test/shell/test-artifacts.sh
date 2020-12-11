#!/bin/bash
# test artifacts
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 1] pipeline with artifacts
#

PROJECT_DIR=../..
TEST_BUILD_DIR=build/artifacts-test

case ${1-0} in
  0 ) echo "# 0: $0 run"
      run_test "$0" 1
      exit
      ;;
  1 ) echo "# 1: pipeline with artifacts"
      cd "$PROJECT_DIR"
      rm -rf -- "$TEST_BUILD_DIR"
      bin/pipelines --file test/data/yml/artifacts.yml
      ls -al -- "$TEST_BUILD_DIR"
      exit
      ;;
  * ) >&2 echo "unknown step $1"
      exit 1
      ;;
esac
