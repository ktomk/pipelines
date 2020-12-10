--TEST--
build w/o file
--INI--
phar.readonly=0
--FILE--
<?php

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Ktomk\Pipelines\PharBuild\Builder;

chdir('test/data/phar');

$builder = Builder::create('');
$builder
    ->build()
;

__HALT_COMPILER(); ?>
--EXPECT--
no files, add some or do not remove all
fatal: build has errors, not building

