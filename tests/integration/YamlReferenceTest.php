<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\TestCase;

/**
 * @coversNothing
 */
class YamlReferenceTest extends TestCase
{
    public function testFileWithAliasParses()
    {
        $file = File::createFromFile(__DIR__ . '/../data/yml/alias.yml');
        $actual = $file->getPipelines()->getPipelineIds();
        $idDefault = 'default';
        $idAlias = 'branches/feature/*';
        $expected = array(
            $idDefault,
            $idAlias
        );
        $this->assertSame($expected, $actual, 'alias is loaded');

        $this->assertNotSame(
            $default = $file->getById($idDefault),
            $alias = $file->getById($idAlias),
            'alias yaml node must create their identities'
        );

        $this->assertSame(
            $default->jsonSerialize(),
            $alias->jsonSerialize(),
            'alias yaml node must create their nodes'
        );
    }
}
