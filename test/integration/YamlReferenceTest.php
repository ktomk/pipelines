<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\ReferenceTypes;
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
        $idDefault = ReferenceTypes::SEG_DEFAULT;
        $idAlias = ReferenceTypes::REF_BRANCHES . '/feature/*';
        $expected = array(
            $idDefault,
            $idAlias,
        );
        self::assertSame($expected, $actual, 'alias is loaded');

        self::assertNotSame(
            $default = $file->getById($idDefault),
            $alias = $file->getById($idAlias),
            'alias yaml node must create their identities'
        );

        self::assertSame(
            $default->jsonSerialize(),
            $alias->jsonSerialize(),
            'alias yaml node must create their nodes'
        );
    }
}
