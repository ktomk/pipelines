<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\TestCase;

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
        $this->assertNotNull($options);
    }
}
