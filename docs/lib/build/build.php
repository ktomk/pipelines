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

list(, $file) = $argv + array(null, 'build/pipelines.phar');

list($version, $error) = \Ktomk\Pipelines\Utility\Version::gitComposerVersion();
if (null === $version) {
    fprintf(STDERR, "fatal: %s\n", $error);
    exit(1);
}

printf("building %s ...\n", $version);
if (false === putenv(sprintf('COMPOSER_ROOT_VERSION=%s', $version))) {
    fprintf(STDERR, "fatal: failed to put version into COMPOSER environment\n");
    exit(1);
}

$builder = Builder::create($file);
$builder
    ->stubfile(__DIR__ . '/stub.php')
    ->add('bin/pipelines', $builder->dropFirstLine()) # utility executable
    ->add('COPYING')
    ->add('src/**/*.php') # utility php files
    ->add('src/Utility/App.php', $builder->replace('@.@.@', $version)) # set version
    ->add('lib/package/*.yml') # docker client packages
    ->remove('lib/package/docker-42.42.1-binsh-test-stub.yml', false) # test fixture
    // FIXME ;!pattern
    ->remove('src/Cli/Vcs**') # vcs integration stub (unused)
    ->remove('src/PharBuild/*') # phar build
    ->remove('src/Composer.php') # composer scripts
    // Composer autoloader has a flaw and requires a full install w/ --no-dev
    // for the non-dev autoloader used in the phar file
    ->phpExec('composer -n -q install --ignore-platform-reqs --no-dev')
    ->add('vendor/{,composer/}*.php', $builder->snapShot())
    ->phpExec('composer -n -q install --ignore-platform-reqs')
    // Dependencies
    ->add('vendor/ktomk/symfony-yaml/{,Exception/}{{Yaml,Inline,Parser,Unescaper,Except*,ParseEx*,Runt*}.php,LICENSE}')
    ->add('lib/pipelines/schema/pipelines-schema.json')
    ->add('vendor/justinrainbow/json-schema/src/JsonSchema/**/*.php')
    ->remove('vendor/justinrainbow/json-schema/src/JsonSchema/Uri/Retrievers/{Curl,PredefinedArray}.php')
    // build phar archive, reset timestamps
    ->build('--version')
    ->exec('git log -n1 --pretty=%ci HEAD', $timestamp)
    ->timestamps($timestamp)
    ->info();

exit($builder->errors() ? 1 : 0);
