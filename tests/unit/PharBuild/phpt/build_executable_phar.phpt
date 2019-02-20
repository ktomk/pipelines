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
size.....: 597 bytes
SHA-1....: F25E5701208515A6E210833CED55037C979BB032
SHA-256..: F0A1372DF8F7245C29F4EF60497AA57C618DB77D986239B30EE7D8518F9A472B
file.....: %d.%d.%d
api......: %d.%d.%d
extension: %d.%d.%s
php......: %d.%d.%s
uname....: %s
count....: 2 file(s)
signature: SHA-1 0F44767AC97AC531ED263761601BE2CF4F9D13D3
