<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\TestCase;

/**
 * Class OptionsTest
 *
 * @covers \Ktomk\Pipelines\Utility\Options
 * @covers \Ktomk\Pipelines\Utility\OptionsMock
 */
class OptionsTest extends TestCase
{
    public function testCreate()
    {
        $options = Options::create();
        $this->assertNull($options->get('foo.bar.baz'));
        $this->assertInternalType('string', $options->get('docker.socket.path'));
    }

    public function testOptionsMock()
    {
        $options = OptionsMock::create();

        $this->assertNull($options->get('foo.bar.baz'));
        $this->assertNotNull($options->define('foo.bar.baz', 'top')->get('foo.bar.baz'));
        $this->assertSame('top', $options->get('foo.bar.baz'));

        $this->assertInternalType('string', $options->get('docker.socket.path'));
        $options->define('docker.socket.path', '/var/run/super-docker.sock');
        $this->assertSame('/var/run/super-docker.sock', $options->get('docker.socket.path'));
    }
}
