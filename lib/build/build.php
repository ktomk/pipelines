<?php

/*
 * this file is part of pipelines
 *
 * build phar file
 *
 * usage: composer build
 */

use Ktomk\Pipelines\PharBuild\Builder;

require __DIR__ . '/../../src/bootstrap.php';

$version = exec('echo "$(git describe --tags --always --first-parent)$(git diff-index --quiet HEAD -- || echo +)"');

printf("building %s ...\n", $version);

$builder = Builder::create('build/pipelines.phar');
$builder
    ->stubfile(__DIR__ . '/stub.php')
    ->add('bin/pipelines', $builder->dropFirstLine())
    ->add('COPYING')
    ->add('src/{**/,}*.php')
    ->add('src/Utility/App.php', $builder->replace('@.@.@', $version))
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
    ->add('vendor/mustangostang/spyc/Spyc.php')
    // build phar archive, reset timestamps
    ->build('--version')
    ->exec('git log -n1 --pretty=%ci HEAD', $timestamp)
    ->timestamps($timestamp)
    ->info();

exit($builder->errors() ? 1 : 0);
