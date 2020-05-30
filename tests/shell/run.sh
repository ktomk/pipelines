#!/bin/bash
#
# this file is part of pipelines
#
# test runner for shell test-drivers
#
# usage: ./tests/shell/run.sh [<case> [<test>..]]
#
# Standard options
#     <case>       test-case script, test-*.sh, path relative to the
#                  runner
#     <test>..     test-number/s of a test-case, the number 0 to run
#                  the test-case/drivers' test-plan, any number greater
#                  than zero runs that test number of the driver
#
# example: ./tests/shell/run.sh test-phar.sh 1 2
#
set -euo pipefail
IFS=$'\n\t'

cd "${0%/*}"

##
# optional: <case> (test-*.sh) [<test>..] as positional parameters
if [[ -f "./${1:-}" ]] && [[ -x "./${1:-}" ]]; then
  test="./${1}"
  if [[ "$#" -eq 1 ]]; then
    "${test}"
    exit
  fi
  while [[ "$#" -gt 1 ]]; do
    shift 1
    "${test}" "${1}"
  done
  exit
fi
if [[ "$#" -gt 1 ]]; then
  >&2 echo "fatal: not a test-case: '${1}'"
  exit 1
fi

./test-services.sh 1 2
./test-pip.sh
