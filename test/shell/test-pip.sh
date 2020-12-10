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
# [ 6] list available client packages
# [ 5] docker service inside pipelines w/ test package
# [ 4] docker service inside pipelines
# [ 2] pipelines inside pipelines
# [ 3] pipelines inside pipelines (services: - docker)
# [ 7] relative docker client package path w/ --working-dir
# [ 8] recursion detection (non-phar)
# [ 9] pip recursion happy path (non-phar)
#

case ${1-0} in
  0 ) echo "# 0: ${0} run"
      run_test "${0}" 8 1 6 5 4 2 3 7
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
      set +e # must fail, "test" is invalid
      ../../build/pipelines.phar --docker-client 'test' --pipeline custom/docker 2>&1 | grep given
      ret=$?
      set -e
      [ $ret -ne 0 ]
      set +e # must fail, "docker-42.42.1-binsh-test-stub" is invalid
      ../../build/pipelines.phar --docker-client 'docker-42.42.1-binsh-test-stub' --pipeline custom/docker 2>&1 | grep given
      ret=$?
      set -e
      [ $ret -ne 0 ]
      ls -al ../../lib/package/docker-42.42.1-binsh-test-stub.yml
      ../../build/pipelines.phar --docker-client './lib/package/docker-42.42.1-binsh-test-stub.yml' --pipeline custom/docker
      exit
      ;;
  6 ) echo "# 6: list available client packages"
      ../../build/pipelines.phar --docker-client-pkgs | wc -l | sed -e 's/^/number of packages: /'
      exit
      ;;
  7 ) echo "# 7: relative docker client package path w/ --working-dir"
      # 7.1: implicit working directory by the location of the bitbucket-pipelines.yml file
      ../../build/pipelines.phar \
        --docker-client lib/package/docker-42.42.1-binsh-test-stub.yml \
        --pipeline custom/docker | tail -n 3
      # 7.2: set working directory (which also matches the project)
      ../../build/pipelines.phar --working-dir ../.. \
        --docker-client lib/package/docker-42.42.1-binsh-test-stub.yml \
        --pipeline custom/docker | tail -n 3
      # 7.3: no implicit working directory
      ../../build/pipelines.phar --file ../../bitbucket-pipelines.yml \
        --docker-client ../../lib/package/docker-42.42.1-binsh-test-stub.yml \
        --pipeline custom/docker | tail -n 3

      exit
      ;;
  8 ) echo "# 8: recursion detection (non-phar)"
      ../../bin/pipelines --pipeline custom/recursion || test $? -eq 127
      exit
      ;;
  9 ) echo "# 9: pip recursion happy path (non-phar)"
      ../../bin/pipelines --pipeline custom/recursion-pip-happy
      exit
      ;;
  * ) >&2 echo "unknown step ${1}"
      exit 1
      ;;
esac
