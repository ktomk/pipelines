--TEST--
use builder to build a phar file, happy path feature test
--INI--
phar.readonly=0
--FILE--
<?php

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Ktomk\Pipelines\PharBuild\Builder;

chdir('tests/data/phar');

$builder = Builder::create('build/test.phar');
$builder
    ->stubfile('stub.php')
    ->limit(0)
    ->add('test', $builder->dropFirstLine())
    ->add('test.php', null, '../phar', null)
    ->add('.gitignore', null, 'build', 'build')
    ->remove('build/.*')
    ->build('--version')
    ->timestamps()
    ->info()
    ->timestamps('today')
    ->timestamps(new DateTime('now'))
;

unset($builder); # trigger __destruct

__HALT_COMPILER(); ?>
--EXPECTF--
echo a hello from test.php
file.....: build/test.phar
size.....: 639 bytes
SHA-1....: FC75D09CC614E35D10ACF19E53C1B3FA986C8F8D
SHA-256..: 477F1CB68398D847EDC9EAB91CEE7E30FEA51BA3C44C76243FD89C4CF6D7AF77
file.....: %d.%d.%d
api......: %d.%d.%d
extension: %d.%d.%s
php......: %d.%d.%s
composer.: %s
uname....: %s
count....: 2 file(s)
signature: SHA-1 1F7B029FA816B7668EEBADAACF86C039CA4B1E1B
