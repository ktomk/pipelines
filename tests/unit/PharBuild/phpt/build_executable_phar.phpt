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
--EXPECT--
echo a hello from test.php
file.....: build/test.phar
size.....: 597 bytes
SHA-1....: 8c19b670e79bf08d9d17dcdb74e378a0649f6886
SHA-256..: 7f605388b3be3f8b2be5c6e07c50f41cb40c950893e14fbdb2a45a1e2e4b63d3
count....: 2 file(s)
signature: SHA-1 CFF2499E7B488089C84BEB892730D8C7C53BD728
