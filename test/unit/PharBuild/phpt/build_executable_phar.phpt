--TEST--
use builder to build a phar file, happy path feature test
--INI--
phar.readonly=0
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
SHA-1....: fc75d09cc614e35d10acf19e53c1b3fa986c8f8d
SHA-256..: 477f1cb68398d847edc9eab91cee7e30fea51ba3c44c76243fd89c4cf6d7af77
file-ver.: %d.%d.%d
api......: %d.%d.%d
extension: %d.%d.%s
php......: %d.%d.%s
composer.: %s
uname....: %s
count....: 2 file(s)
signature: SHA-1 1F7B029FA816B7668EEBADAACF86C039CA4B1E1B
