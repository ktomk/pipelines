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
SHA-1....: 8C19B670E79BF08D9D17DCDB74E378A0649F6886
SHA-256..: 7F605388B3BE3F8B2BE5C6E07C50F41CB40C950893E14FBDB2A45A1E2E4B63D3
api......: %d.%d.%d
count....: 2 file(s)
signature: SHA-1 CFF2499E7B488089C84BEB892730D8C7C53BD728
