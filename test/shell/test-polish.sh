#!/bin/bash
# test different things while reviewing for improvements
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 3] keep and reuse container
# [ 2] run w/ script generation and verbose output (w/ services: - docker)
# [ 1] run w/ script generation and verbose output
#

case ${1-0} in
  0 ) echo "# 0: $0 run"
      run_test "$0" 3 2 1
      exit
      ;;
  1 ) echo "# 1: run w/ script generation and verbose output"
      # shows dry-run issue w/ docker client install:
      ../../bin/pipelines -v --dry-run --pipeline default
      exit
      ;;
  2 ) echo "# 2: run w/ script generation and verbose output (w/ services: - docker)"
      # shows dry-run issue w/ docker client install:
      ../../bin/pipelines -v --dry-run --pipeline custom/unit-tests
      exit
      ;;
  3 ) echo "# 3: keep and reuse container"
      ../../bin/pipelines --docker-zap >/dev/null
      ../../bin/pipelines --keep | grep 'keeping container id'
      docker ps | grep 'pipelines-1.pipeline-features-and-introspection.default.pipelines'
      ../../bin/pipelines --keep | grep 'keeping container id'
      docker ps | grep 'pipelines-1.pipeline-features-and-introspection.default.pipelines'
      ../../bin/pipelines | tail -1 | grep -v 'keeping container id'
      docker ps | grep -v 'pipelines-1.pipeline-features-and-introspection.default.pipelines'
      exit
      ;;
  * ) >&2 echo "unknown step $1"
      exit 1
      ;;
esac
