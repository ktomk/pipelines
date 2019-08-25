<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\TestCase;

/**
 * Class StepServicesTest
 *
 * @covers \Ktomk\Pipelines\File\StepServices
 *
 * @package Ktomk\Pipelines\File
 */
class StepServicesTest extends TestCase
{
    public function testCreation()
    {
        $services = new StepServices($this->createMock('Ktomk\Pipelines\Step'), array());
        $this->assertInstanceOf('Ktomk\Pipelines\File\StepServices', $services);
    }

    public function testCreationParseException()
    {
        $this->setExpectedException('Ktomk\Pipelines\File\ParseException', '\'services\' requires a list of services');

        $yaml = (object)array();
        $services = new StepServices($this->createMock('Ktomk\Pipelines\Step'), $yaml);
        $this->assertInstanceOf('Ktomk\Pipelines\File\StepServices', $services);
    }

    public function testCreationCauseRealProblems()
    {
        $this->setExpectedException('Ktomk\Pipelines\File\ParseException', '\'services\' service name string expected');

        $yaml = array('fine', array('scrap'), (object)array());
        $services = new StepServices($this->createMock('Ktomk\Pipelines\Step'), $yaml);
        $this->assertInstanceOf('Ktomk\Pipelines\File\StepServices', $services);
    }

    public function testHas()
    {
        $services = new StepServices($this->createMock('Ktomk\Pipelines\Step'), array('docker'));
        $this->assertTrue($services->has('docker'));
        $this->assertFalse($services->has('mysql'));
    }
}
