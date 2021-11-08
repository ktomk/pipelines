#!/bin/sh
#
# ppconf.sh - php project configuration script
#
# examples:
#     ./lib/script/ppconf.sh help
#     ./lib/script/ppconf.sh self-test
#     ./lib/script/ppconf.sh disable-tls
#     ./lib/script/ppconf.sh patch-phpunit-tests
#     ./lib/script/ppconf.sh downpatch-phpunit-tests
#     ./lib/script/ppconf.sh phpunit-version ^4
#     ./lib/script/ppconf.sh remove-dev kubawerlos/php-cs-fixer-custom-fixers
#     ./lib/script/ppconf.sh xdebug3
#
set -eu
IFS=$(printf '\n \t')

PHP_BINARY="${PHP_BINARY-php}"

### run composer with PHP_BINARY/php
f_composer() {
  "${PHP_BINARY}" -f "$(composer which 2>/dev/null)" -- "$@"
}

if [ $# -eq 0 ]; then
  set - help
fi

while [ $# -gt 0 ]; do
  case ${1-0} in

    help ) ### show usage
      # printf "ppconf %s\n" "$1" # commented-out by intention
      printf "usage: %s [directive [parameter ...] ...]\n" "$0"
      printf "\n"
      printf "Directives\n"
      printf "    help                  show usage help\n"
      printf "    self-test             show self/system info\n"
      printf "    disable-tls           http only composer\n"
      printf "    patch[-phpunit]-tests testsuite phpunit compatibility (phpunit 8 for php 7.4)\n"
      printf "    downpatch[-phpunit]-tests\n"
      printf "                          testsuite phpunit compatibility (phpunit < 8; php 7.4)\n"
      printf "    phpunit[-version] <ver>\n"
      printf "                          set phpunit version, <ver> example: ^4\n"
      printf "    [patch-]xdebug2[-tests]\n"
      printf "                          un-patch tests for xdebug 2 after patched for xdebug 3\n"
      printf "    [patch-]xdebug3[-tests]\n"
      printf "                          patch tests for xdebug 3\n"
      printf "    remove-dev <deps>     remove dev-requirements (composer)\n"
      printf "\n"
      shift
      ;;

    self-test ) ### test-list utils (in-use, additional and system-info)
      printf "ppconf %s\n" "$1"
      printf "bash....: %-32s\t%s\n"      "$(which bash         )" "$(bash          --version 2>/dev/null | head -1)"
      printf "composer: %-32s\t%s (%s)\n" "$(which composer     )" "$(composer      --version 2>/dev/null | head -1)" \
                                                                   "$(composer      which               2>/dev/null)"
      printf "docker..: %-32s\t%s\n"      "$(which docker       )" "$(docker        --version 2>/dev/null | head -1)"
      printf "find....: %-32s\t%s\n"      "$(which find         )" "$(find          --version 2>&1        | head -1)"
      printf "gpg.....: %-32s\t%s\n"      "$(which gpg          )" "$(gpg           --version 2>/dev/null | head -1)"
      printf "make....: %-32s\t%s\n"      "$(which make         )" "$(make          --version 2>/dev/null | head -1)"
      printf "openssl.: %-32s\t%s\n"      "$(which openssl      )" "$(openssl         version 2>/dev/null | head -1)"
      (cat /etc/os-release || cat /etc/issue) 2>/dev/null   | head -n 3 | sed 's/^/os......: /'
      printf "php.....: %-32s\t%s (%s)\n" "$(which "$PHP_BINARY")" "$("$PHP_BINARY" --version 2>&1        | head -1)" \
                                                                   "$(composer      which-php           2>/dev/null)"
      printf "python..: %-32s\t%s\n"      "$(which python       )" "$(hash python 2>/dev/null &&                      \
                                                                      python        --version 2>&1        | head -1)"
      printf "python3.: %-32s\t%s\n"      "$(which python3      )" "$(python3       --version 2>/dev/null | head -1)"
      printf "sed.....: %-32s\t%s\n"      "$(which sed          )" "$(sed           --version 2>&1        | head -1)"
      printf "sh......: %-32s\t%s\n"      "$(which sh           )" "$(realpath "$(which sh)")"
      printf "tar.....: %-32s\t%s\n"      "$(which tar          )" "$(tar           --version 2>/dev/null | head -1)"
      printf "unzip...: %-32s\t%s\n"      "$(which unzip        )" "$(unzip -v                2>&1        | head -1)"
      printf "xdebug..: %s\n"             "$(php -derror_reporting=-1 -r 'echo phpversion("xdebug") ?: "no", "\n";')"

      shift
      ;;

    disable-tls ) ### use http - not https - in composer (degrades security)
      printf "ppconf %s\n" "$1"
      f_composer config secure-http false && f_composer config disable-tls true
      shift
      ;;

    patch-tests | patch-phpunit-tests) ### add php syntax for higher php version phpunit requirements
      # for phpunit 8 (php version breakpoint: 7.4)
      printf "ppconf %s\n" "$1"
      sed -i \
        -e '/protected function create.*Mock/ s/)$/): MockObject/' \
        -e '/public static function assert.*/ s/)$/): void/' \
        -e '/public function expect.*/ s/)$/): void/' \
        test/TestCase.php
      find test -type f -name '*Test*.php' \
        -exec sed -i -e '/ setUp(/ s/)$/): void/' -e '/ tearDown(/ s/)$/): void/' {} \;
      shift
      ;;

    downpatch-tests | downpatch-phpunit-tests ) ### remove php syntax for higher php version phpunit requirements
      # for phpunit < 8 (php version breakpoint: 7.4)
      printf "ppconf %s\n" "$1"
      sed -i \
        -e '/protected function create.*Mock/ s/): MockObject$/)/' \
        -e '/public static function assert.*/ s/): void$/)/' \
        -e '/public function expect.*/ s/): void$/)/' \
        test/TestCase.php
      find test -type f -name '*Test*.php' \
        -exec sed -i -e '/ setUp(/ s/): void$/)/' -e '/ tearDown(/ s/): void$/)/' {} \;
      shift
      ;;

    xdebug2 | patch-xdebug2-tests ) ###  xdebug.mode=off -> xdebug.default_enable=0
      printf "ppconf %s\n" "$1"
      find test -type f -name '*.phpt' \
        -exec sed -i -e '/^xdebug.mode=coverage$/ s/^.*$/xdebug.default_enable=0/' {} \;
      shift
      ;;

    xdebug3 | patch-xdebug3-tests ) ### xdebug.default_enable=0 -> xdebug.mode=off
      printf "ppconf %s\n" "$1"
      find test -type f -name '*.phpt' \
        -exec sed -i -e '/^xdebug.default_enable=0$/ s/^.*$/xdebug.mode=coverage/' {} \;
      shift
      ;;

    phpunit | phpunit-version ) ### composer require specific phpunit version
      printf "ppconf %s: %s\n" "$1" "$2"
      if ! f_composer show -i --name-only phpunit/phpunit 2>/dev/null | head -n 4 | sed -n -e 's/versions.*\* //p' | grep -q "$2"'\.'; then
        f_composer --quiet require --dev --update-with-dependencies phpunit/phpunit:"$2"
      fi
      shift 2
      ;;

    remove-dev ) ### composer remove development dependencies
      printf "ppconf %s: %s\n" "$1" "$2"
      deps="$2"
      if [ "$deps" = "all" ]; then
        deps="friendsofphp/php-cs-fixer kubawerlos/php-cs-fixer-custom-fixers phpunit/phpunit"
        printf "       %s: %s\n" "$1" "$deps"
      fi
      if [ "$deps" = "optional" ]; then
        deps="friendsofphp/php-cs-fixer kubawerlos/php-cs-fixer-custom-fixers"
        printf "       %s: %s\n" "$1" "$deps"
      fi
      # shellcheck disable=SC2086 # unquoted $deps (from $2) intended
      f_composer --quiet remove --dev --ignore-platform-reqs $deps
      shift 2
      ;;

    error-exit ) ### internal
      exit 1
      ;;

    * ) ### unknown
      printf "fatal: unknown directive '%s'\n" "$1"
      set - help error-exit
      ;;

  esac;
done;
