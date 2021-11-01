#!/bin/bash
# test phar creation / modification
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 1] redis service (kept)
# [ 2] redis service (clean)
# [ 3] mysql service
#

PROJECT_DIR=../..

case ${1-0} in
  0 ) echo "# 0: $0 run"
      run_test "$0" 1 2 3
      exit
      ;;
  1 ) echo "# 1: redis service (kept)"
      "$PROJECT_DIR/bin/pipelines" --pipeline custom/redis-service --keep --verbatim | grep PONG
      "$PROJECT_DIR/bin/pipelines" --docker-list | grep pipelines-1.redis-service.custom-redis-service.'[a-z]\+$'
      exit
      ;;
  2 ) echo "# 2: redis service (clean)"
      "$PROJECT_DIR/bin/pipelines" --pipeline custom/redis-service --verbatim | grep PONG
      ! ("$PROJECT_DIR/bin/pipelines" --docker-list | grep pipelines-1.redis-service.custom-redis-service.'[a-z]\+$')
      exit
      ;;
  3 ) echo "# 3: mysql service"
      "$PROJECT_DIR/bin/pipelines" --pipeline custom/mysql-service
      exit
      ;;
  * ) >&2 echo "unknown step $1"
      exit 1
      ;;
esac
