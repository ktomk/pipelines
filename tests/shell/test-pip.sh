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
# [ 5] docker service inside pipelines w/ test package
# [ 4] docker service inside pipelines
# [ 2] pipelines inside pipelines
# [ 3] pipelines inside pipelines (services: - docker)
#

case ${1-0} in
  0 ) echo "# 0: ${0} run"
      run_test "${0}" 1 5 4 2 3
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
  2 ) echo "# 2: run pipelines (install docker client)"
      ../../build/pipelines.phar --pipeline custom/docker-phar-install
      exit
      ;;
  3 ) echo "# 3: pipelines inside pipelines (services: - docker)"
      ../../build/pipelines.phar --pipeline custom/docker-phar
      exit
      ;;
  4 ) echo "# 4: docker service inside pipelines"
      ../../build/pipelines.phar --pipeline custom/docker
      exit
      ;;
  5 ) echo "# 5: docker service inside pipelines w/ test package"
      ../../build/pipelines.phar --docker-client 'test' --pipeline custom/docker | grep 'fatal: not a readable file:' || true # must fail, "test" is invalid"
      ../../build/pipelines.phar --verbatim --docker-client 'docker-42.42.1-binsh-test-stub' --pipeline custom/docker
      exit
      ;;
  * ) >&2 echo "unknown step ${1}"
      exit 1
      ;;
esac
