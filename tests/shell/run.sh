#!/bin/bash
#
# this file is part of pipelines
#
# test runner for shell test-drivers
set -euo pipefail
IFS=$'\n\t'

cd "${0%/*}"

##
# test-...sh passed as positional parameter executes directly
if [[ -f "./${1:-}" ]] && [[ -x "./${1:-}" ]]; then
  test="./${1}"
  shift 1
  "${test}" "${@}"
  exit
fi

./test-pip.sh
