<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\Runner\RunnerTestCase;

/**
 * Class StepServicesTest
 *
 * @covers \Ktomk\Pipelines\File\Pipeline\StepServices
 *
 * @package Ktomk\Pipelines\File\File
 */
class StepServicesTest extends RunnerTestCase
{
    public function testCreation()
    {
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), array());
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepServices', $services);
    }

    public function testCreationParseException()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'services\' requires a list of services');

        $yaml = (object)array();
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepServices', $services);
    }

    public function testCreationCauseRealProblems()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'services\' service name string expected');

        $yaml = array('fine', array('scrap'), (object)array());
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepServices', $services);
    }

    public function testHas()
    {
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), array('docker'));
        self::assertTrue($services->has('docker'));
        self::assertFalse($services->has('mysql'));
    }

    public function testGetDefinitions()
    {
        $step = $this->createTestStep();
        $services = new StepServices($step, array('redis', 'docker', 'mysql'));
        self::assertSame(array(), $services->getDefinitions());
    }

    public function testGetDefinitionsWithFileAndDefinitions()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/service-definitions.yml');
        $services = $file->getPipelines()->getDefault()->getSteps()->offsetGet(0)->getServices();
        $actual = $services->getDefinitions();
        self::assertCount(1, $actual);
        self::assertContainsOnlyInstancesOf('Ktomk\Pipelines\File\Definitions\Service', $actual);
    }

    /**
     * @return void
     */
    public function testGetServiceNames()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $services = new StepServices($step, array());

        self::assertSame(array(), $services->getServiceNames());

        $services = new StepServices($step, array('docker', 'foo', 'bar', 'baz'));
        self::assertSame(array('foo', 'bar', 'baz'), $services->getServiceNames());
    }
}
