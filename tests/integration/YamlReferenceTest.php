<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration;

use Ktomk\Pipelines\File;
use PHPUnit\Framework\TestCase;

class YamlReferenceTest extends TestCase
{
    public function testFileWithAliasParses()
    {
        $file = File::createFromFile(__DIR__ . '/../data/alias.yml');
        $actual = $file->getPipelineIds();
        $idDefault = 'default';
        $idAlias = 'branches/feature/*';
        $expected = array(
            $idDefault,
            $idAlias
        );
        $this->assertSame($expected, $actual, 'alias is loaded');

        $this->assertNotSame(
            $file->getById($idDefault),
            $file->getById($idAlias),
            'alias yaml node must create their identities'
        );

        $this->assertEquals(
            $file->getById($idDefault),
            $file->getById($idAlias),
            'alias yaml node must create their nodes'
        );
    }
}
