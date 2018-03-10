#!/bin/sh
#
# run phpunit from composer (dev) dependencies
#
# usage: lib/pipelines/phpunit.sh
#
set -u
IFS="$(printf '\n\t ')"

vendor/bin/phpunit
