--TEST--
builder can not unlink existing phar file
--INI--
phar.readonly=0
xdebug.default_enable=0
--FILE--
<?php

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Ktomk\Pipelines\PharBuild\Builder;

chdir('tests/data/phar') || exit('could not change directory');

$builder = Builder::create('build');
$builder
    ->build()
;

__HALT_COMPILER(); ?>
--EXPECTF--
Warning: unlink(build): %s in %s on line %d
could not unlink existing file 'build'
