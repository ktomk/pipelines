#!/bin/bash
# test phar creation / modification
set -euo pipefail
IFS=$'\n\t'

# TEST SETUP
. tests-framework.sh # include test framework

#
#               TEST PLAN
#
# [ 1] build pipelines phar
# [ 2] check last file by checksum
#

PROJECT_DIR=../..

case ${1-0} in
  0 ) echo "# 0: $0 run"
      run_test "$0" 1 2
      exit
      ;;
  1 ) echo "# 1: build pipelines phar"
      cd "$PROJECT_DIR"
      assert lib/build/build.php build/test.phar | grep 'signature:'
      build/test.phar --version
      exit
      ;;
  2 ) echo "# 2: check last file by checksum"
      cd "$PROJECT_DIR"
    <<'EOD' "${PHP_BINARY-php}" -f /dev/stdin -- \
          build/test.phar \
          vendor/ktomk/symfony-yaml/Symfony/Component/Yaml/Yaml.php
<?php
$pharFile = $argv[1];
$path = $argv[2];
$phar = "phar://$pharFile/$path";
function chk_file($label, $path) {
  $buffer = file_get_contents($path);
  $lenBuffer = strlen($buffer);
  $fileSize = filesize($path);
  printf("  %'.-4s: %d %d %s \"%s\"\n"
    , $label, $fileSize, $lenBuffer, md5_file($path)
    , addcslashes(substr($buffer, -1), "\0..\37!@\177..\377")
  );
}
printf("%s: %d\n", $pharFile, filesize($pharFile));
printf("%s:\n", basename($path));
chk_file('fs', $path);
chk_file('phar', $phar);
EOD
      exit
      ;;
  * ) >&2 echo "unknown step $1"
      exit 1
      ;;
esac
