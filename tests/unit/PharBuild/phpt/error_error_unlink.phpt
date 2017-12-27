--TEST--
builder can not unlink existing phar file
--INI--
phar.readonly=0
--FILE--
<?php

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Ktomk\Pipelines\PharBuild\Builder;

chdir('tests/data/phar') || exit('could not change directory');

$builder = Builder::create('/dev/null');
$builder
    ->build()
;

__HALT_COMPILER(); ?>
--EXPECTF--
Warning: unlink(/dev/null): Permission denied in %s on line %d
could not unlink existing file '/dev/null'
