<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Definitions;

use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Yaml\Yaml;

/**
 * Class ServicesTest
 *
 * @package Ktomk\Pipelines\File\Definitions
 *
 * @covers \Ktomk\Pipelines\File\Definitions\Services
 */
class ServicesTest extends TestCase
{
    /**
     * @return Services
     */
    public function testCreation()
    {
        $services = new Services(array());
        self::assertInstanceOf('Ktomk\Pipelines\File\Definitions\Services', $services);

        return $services;
    }

    /**
     * @depends testCreation
     *
     * @param Services $services
     *
     * @return void
     */
    public function testGetNonExistingService(Services $services)
    {
        self::assertNull($services->getByName('foo'));
    }

    /**
     * @depends testCreation
     *
     * @param Services $services
     *
     * @return void
     */
    public function testGetNonExistingServicesByNames(Services $services)
    {
        self::assertSame(array(), $services->getByNames(array('foo', 'docker', 'baz')));
    }

    public function testParsing()
    {
        $array = Yaml::file(__DIR__ . '/../../../data/yml/service-definitions.yml');
        self::assertArrayHasKey('definitions', $array, 'fixture complete 1');
        self::assertIsArray($array['definitions'], 'fixture complete 2');
        self::assertArrayHasKey('services', $array['definitions'], 'fixture complete 3');
        self::assertIsArray($array['definitions']['services'], 'fixture complete 4');

        $services = new Services($array['definitions']['services']);
        self::assertInstanceOf('Ktomk\Pipelines\File\Definitions\Services', $services);

        self::assertCount(2, $services);
    }

    /**
     * docker service is different as it does not need an image.
     *
     * @return void
     */
    public function testParseDockerService()
    {
        $array = Yaml::file(__DIR__ . '/../../../data/yml/service-docker-memory.yml');
        self::assertArrayHasKey('definitions', $array, 'fixture complete 1');
        self::assertIsArray($array['definitions'], 'fixture complete 2');
        self::assertArrayHasKey('services', $array['definitions'], 'fixture complete 3');
        self::assertIsArray($array['definitions']['services'], 'fixture complete 4');

        $services = new Services($array['definitions']['services']);
        self::assertInstanceOf('Ktomk\Pipelines\File\Definitions\Services', $services);

        self::assertCount(0, $services);
    }

    /**
     * @return void
     */
    public function testParseInvalidServiceName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('file parse error: Invalid service definition name: 0');
        new Services(array('foo'));
    }

    /**
     * @return void
     */
    public function testParseInvalidService()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('file parse error: Invalid service definition "foo"');
        new Services(array('foo' => null));
    }
}

