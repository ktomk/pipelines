#!/bin/bash
#
# this file is part of pipelines
#
# test runner for shell test-drivers
#
# usage: ./test/shell/run.sh [<case> [<test>..]]
#
# Standard options
#     <case>       test-case script, test-*.sh, path relative to the
#                  runner
#     <test>..     test-number/s of a test-case, the number 0 to run
#                  the test-case/drivers' test-plan, any number greater
#                  than zero runs that test number of the driver
#
# example: ./test/shell/run.sh phar 1 2
#
set -euo pipefail
IFS=$'\n\t'
CDPATH=""
export CDPATH

cd "${0%/*}"

##
# run <case> (test->>*<<.sh) [<test>..] as positional parameters
run() {
  local test
  test="./test-${1:-}.sh"
  if [[ -f "$test" ]] && [[ -x "$test" ]]; then
    if [[ "$#" -eq 1 ]]; then
      "$test"
      return
    fi
    while [[ "$#" -gt 1 ]]; do
      shift 1
      echo "## $test $1"
      "$test" "$1"
    done
    return
  fi
  if [[ "$#" -gt 1 ]]; then
    >&2 echo "fatal: not a test-case: '$1'"
    return 1
  fi
}

if [ $# -gt 0 ]; then
  run "$@"
  exit
fi

run smoke
run artifacts
run schema-validate
run services 1 2
run pip
