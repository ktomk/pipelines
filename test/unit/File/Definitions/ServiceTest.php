<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Definitions;

use Ktomk\Pipelines\File\ParseException;
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
            'image' => 'busybox',
        );

        $service = new Service($name, $array);
        self::assertInstanceOf('Ktomk\Pipelines\File\Definitions\Service', $service);
        self::assertSame($name, $service->getName());
        self::assertInstanceOf('Ktomk\Pipelines\File\Image', $service->getImage());
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
            'image' => 'busybox',
        );

        $service = new Service($name, $array);
        self::assertSame(array(), $service->getVariables());

        $array['variables'] = array('FOO' => '$BAR', 'BAR' => 'BAZ');
        $service = new Service($name, $array);
        self::assertSame(array('FOO' => '$BAR', 'BAR' => 'BAZ'), $service->getVariables());
    }

    public function testVariablesParseError()
    {
        $name = 'foo';

        $array = array(
            'image' => 'busybox',
            'variables' => null,
        );
        $this->assertNew($name, $array, 'variables must be a list of strings');

        $array['variables'] = array('VAR' => true);
        $this->assertNew($name, $array, 'variable VAR should be a string (it is currently defined as a boolean)');

        $array['variables'] = array('VAR' => null);
        $this->assertNew($name, $array, 'variable VAR should be a string value (it is currently null or empty)');
    }

    private function assertNew($name, $array, $message)
    {
        try {
            new Service($name, $array);
            self::fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            self::assertSame($message, $e->getParseMessage());
        }
    }
}
