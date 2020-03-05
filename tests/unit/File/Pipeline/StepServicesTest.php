<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

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
        $this->setExpectedException('Ktomk\Pipelines\File\ParseException', '\'services\' requires a list of services');

        $yaml = (object)array();
        $services = new StepServices($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepServices', $services);
    }

    public function testCreationCauseRealProblems()
    {
        $this->setExpectedException('Ktomk\Pipelines\File\ParseException', '\'services\' service name string expected');

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
}
