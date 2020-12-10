--TEST--
build w/o not any longer existing file
--INI--
phar.readonly=0
--FILE--
<?php

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Ktomk\Pipelines\PharBuild\Builder;

chdir('test/data/phar') || exit('could not change directory');

touch('build/deleted');

$builder = Builder::create('');
$builder
    ->add('build/deleted')
;
unlink('build/deleted');
$builder->build();

__HALT_COMPILER(); ?>
--EXPECT--
build/deleted: not a file: build/deleted
only 0 of 1 files could be added
fatal: no files in phar archive, must have at least one
