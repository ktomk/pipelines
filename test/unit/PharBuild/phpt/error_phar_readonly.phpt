--TEST--
builder rejects on phar.readonly=1
--INI--
phar.readonly=1
--FILE--
<?php

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Ktomk\Pipelines\PharBuild\Builder;

chdir('test/data/phar');

$builder = Builder::create('build/test.phar');
$builder
    ->stubfile('stub.php')
    ->limit(0)
    ->add('test', $builder->dropFirstLine())
    ->add('test.php')
    ->build('--version')
;

__HALT_COMPILER(); ?>
--EXPECT--
phar: writing phar files is disabled by the php.ini setting 'phar.readonly'
fatal: build has errors, not building

