<?php

/*
 * this file is part of pipelines
 *
 * build phar file
 *
 * usage: composer build
 * usage: php -d phar.readonly=0 -f lib/build/build.php
 */

use Ktomk\Pipelines\PharBuild\Builder;

require __DIR__ . '/../../src/bootstrap.php';

$version = exec('git describe --tags --always --first-parent --dirty=+');

printf("building %s ...\n", $version);

$builder = Builder::create('build/pipelines.phar');
$builder
    ->stubfile(__DIR__ . '/stub.php')
    ->add('bin/pipelines', $builder->dropFirstLine())
    ->add('COPYING')
    ->add('src/**/*.php')
    ->add('src/Utility/App.php', $builder->replace('@.@.@', $version))
    ->add('lib/package/*.yml')
    ->remove('lib/package/docker-42.42.1-binsh-test-stub.yml')
    // FIXME ;!pattern
    // clean up a bit of mess
    ->remove('src/Cli/Vcs**')
    // exclude phar build
    ->remove('src/PharBuild/*')
    // Composer autoloader has a flaw and requires a full install w/ --no-dev
    // for the non-dev autoloader used in the phar file
    ->phpExec('composer -n -q install --no-dev')
    ->add('vendor/{,composer/}*.php', $builder->snapShot())
    ->phpExec('composer -n -q install --ignore-platform-reqs')
    // Dependencies
    ->add('vendor/symfony/yaml/Symfony/Component/Yaml/{,Exception/}{{Yaml,Inline,Parser,Unescaper,Except*,ParseEx*,Runt*}.php,LICENSE}')
    // build phar archive, reset timestamps
    ->build('--version')
    ->exec('git log -n1 --pretty=%ci HEAD', $timestamp)
    ->timestamps($timestamp)
    ->info();

exit($builder->errors() ? 1 : 0);
