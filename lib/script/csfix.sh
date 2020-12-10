#!/bin/bash
#
# run PHP-CS-Fixer fix w/ own rules
#
# e.g. migrate phpunit tests with PHP-CS-Fixer
#
# usage: ./lib/script/csfix.sh <fix-options> -- <path..>
#
# example: lib/script/csfix.sh -- tests/unit tests/integration
#
# Environment
#
#   PHP_CS_FIXER_BIN  path to php-cs-fixer, default is from vendor/bin,
#                     allows to patch php-cs-fixer rules while applying
#                     them.
#
set -euo pipefail
IFS=$'\n\t'

trap 'rm -f -- "$tmp_config_file"' EXIT

tmp_config_file=$(mktemp /tmp/phpunit-migrate-cs-fixer-config.XXXXXX)
<<CONFIG > "${tmp_config_file}" cat
<?php
  return
    PhpCsFixer\Config::create()
      ->setUsingCache(false)
      ->setRiskyAllowed(true)
      ->setRules([
          // assertInternalType(<type> >>> assertIs<type>(
          'php_unit_dedicate_assert_internal_type' => true,

          // @expectedException... >>> ->setExpectedException(...
          'php_unit_no_expectation_annotation' => ['use_class_const' => false],

          // ->setExpectedException...( >>> ->expectException...(
          'php_unit_expectation' => true,
        ])
;
CONFIG

"${PHP_CS_FIXER_BIN-vendor/bin/php-cs-fixer}" fix \
  --config="${tmp_config_file}" \
  "${@}"
