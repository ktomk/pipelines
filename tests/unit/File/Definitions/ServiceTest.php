<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Definitions;

use Ktomk\Pipelines\TestCase;

/**
 * Class ServiceTest
 *
 * @package Ktomk\Pipelines\File\Definitions
 *
 * @covers \Ktomk\Pipelines\File\Definitions\Service
 */
class ServiceTest extends TestCase
{
    public function testParsing()
    {
        $name = 'foo';
        $array = array(
            'image' => 'busybox'
        );

        $service = new Service($name, $array);
        $this->assertInstanceOf('Ktomk\Pipelines\File\Definitions\Service', $service);
        $this->assertSame($name, $service->getName());
        $this->assertInstanceOf('Ktomk\Pipelines\File\Image', $service->getImage());
    }

    /**
     * @return void
     */
    public function testParsingRequiredImage()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage("file parse error: 'image' required in service definition");
        new Service('foo', array());
    }

    public function testVariables()
    {
        $name = 'foo';
        $array = array(
            'image' => 'busybox'
        );

        $service = new Service($name, $array);
        $this->assertSame(array(), $service->getVariables());

        $array['variables'] = array('FOO' => '$BAR', 'BAR' => 'BAZ');
        $service = new Service($name, $array);
        $this->assertSame(array('FOO' => '$BAR', 'BAR' => 'BAZ'), $service->getVariables());
    }
}
