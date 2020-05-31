<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\TestCase;

/**
 * Class StepServicesTest
 *
 * @covers \Ktomk\Pipelines\File\Pipeline\StepServices
 *
 * @package Ktomk\Pipelines\File\File
 */
class StepServicesTest extends TestCase
{
    public function testCreation()
    {
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), array());
        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepServices', $services);
    }

    public function testCreationParseException()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'services\' requires a list of services');

        $yaml = (object)array();
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepServices', $services);
    }

    public function testCreationCauseRealProblems()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'services\' service name string expected');

        $yaml = array('fine', array('scrap'), (object)array());
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepServices', $services);
    }

    public function testHas()
    {
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), array('docker'));
        $this->assertTrue($services->has('docker'));
        $this->assertFalse($services->has('mysql'));
    }

    public function testGetDefinitions()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $services = new StepServices($step, array('redis', 'docker', 'mysql'));
        $this->assertSame(array(), $services->getDefinitions());
    }

    public function testGetDefinitionsWithFileAndDefinitions()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/service-definitions.yml');
        $services = $file->getPipelines()->getDefault()->getSteps()->offsetGet(0)->getServices();
        $actual = $services->getDefinitions();
        $this->assertCount(1, $actual);
        $this->assertContainsOnlyInstancesOf('Ktomk\Pipelines\File\Definitions\Service', $actual);
    }

    /**
     * test (more and more) standard becoming getFile() for the file document object
     */
    public function testGetFile()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $services = new StepServices($step, array());
        $this->assertNull($services->getFile());

        $step->method('getFile')->willReturn($this->createMock('Ktomk\Pipelines\File\File'));
        $services = new StepServices($step, array());
        $this->assertInstanceOf('Ktomk\Pipelines\File\File', $services->getFile());
    }

    /**
     * @return void
     */
    public function testGetServiceNames()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $services = new StepServices($step, array());

        $this->assertSame(array(), $services->getServiceNames());

        $services = new StepServices($step, array('docker', 'foo', 'bar', 'baz'));
        $this->assertSame(array('foo', 'bar', 'baz'), $services->getServiceNames());
    }
}
