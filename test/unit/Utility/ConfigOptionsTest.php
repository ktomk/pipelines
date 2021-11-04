<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Utility\Option\Types;

/**
 * Class ConfigOptionsTest
 *
 * @package Ktomk\Pipelines\Utility
 *
 * @covers \Ktomk\Pipelines\Utility\ConfigOptions
 */
class ConfigOptionsTest extends TestCase
{
    public function testBindAndRun()
    {
        $args = new Args(array('c', '-c', 'foo=flax'));
        $options = ConfigOptions::bind($args)->run();
        self::assertNotNull($options);
    }

    public function testInvalidConfigurationParameter()
    {
        $optionsMock = OptionsMock::create(new Types());
        $optionsMock->define('foo', '/flax', Types::ABSPATH);
        $args = new Args(array('c', '-c', 'foo=flax'));
        $this->expectException('InvalidArgumentException');
        $options = ConfigOptions::bind($args, $optionsMock)->run();
    }
}
