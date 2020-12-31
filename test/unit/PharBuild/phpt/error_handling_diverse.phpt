--TEST--
diverse error handling checks
--INI--
phar.readonly=0
xdebug.mode=coverage
--FILE--
<?php

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Ktomk\Pipelines\PharBuild\Builder;

chdir('test/data/phar');

$builder = Builder::create('build/test.phar');
$builder
    ->stubfile('stub2.php')
    ->add('*.gitignore', null, 'build', '')
    ->add('', null, '--')
    ->add('build/.*', $builder->snapShot())
    ->add('build/.*', function() {return 'wrong';})
    ;

call_user_func($builder->snapShot(), '--');

$builder
    ->build()
    ->timestamps()
    ->info()
    ->remove('foo/no-exist')
    ->remove('**')
    ->remove('foo/all-gone')
    ->exec('false')
;

printf("build errors: %d\n", count($builder->errors()));

__HALT_COMPILER(); ?>
--EXPECTF--
Warning: file_get_contents(stub2.php): %cailed to open stream: No such file or directory in %s on line %d
error reading stubfile: stub2.php
*.gitignore: ineffective alias:%s
ineffective pattern: *.gitignore
invalid directory: --
build/.gitignore: invalid callback return for pattern 'build/.*': 'wrong'

Warning: fopen(--): %cailed to open stream: No such file or directory in %s on line %d
failed to open for reading: --
fatal: build has errors, not building
no such file: build/test.phar
no such file: build/test.phar
ineffective removal pattern: 'foo/no-exist'
can not remove from no files (pattern: 'foo/all-gone')
command failed: false (exit status: 1)
build errors: 12
