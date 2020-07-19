<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Yaml\Yaml;

/**
 * Class DefinitionsTest
 *
 * @package Ktomk\Pipelines\File
 *
 * @covers \Ktomk\Pipelines\File\Definitions
 */
class DefinitionsTest extends TestCase
{
    /**
     * @return Definitions
     */
    public function testParsing()
    {
        $array = Yaml::file(__DIR__ . '/../../data/yml/service-definitions.yml');

        $definitions = new Definitions($array['definitions']);

        self::assertInstanceOf('Ktomk\Pipelines\File\Definitions', $definitions);

        return $definitions;
    }

    /**
     * @depends testParsing
     *
     * @param Definitions $definitions
     *
     * @return void
     */
    public function testGetCaches(Definitions $definitions)
    {
        self::assertInstanceOf(
            'Ktomk\Pipelines\File\Definitions\Caches',
            $definitions->getCaches()
        );
    }

    /**
     * @depends testParsing
     *
     * @param Definitions $definitions
     *
     * @return void
     */
    public function testGetServices(Definitions $definitions)
    {
        self::assertInstanceOf(
            'Ktomk\Pipelines\File\Definitions\Services',
            $definitions->getServices()
        );
    }
}
