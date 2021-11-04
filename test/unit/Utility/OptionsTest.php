<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Utility\Option\Types;

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
        self::assertNull($options->get('foo.bar.baz'));
        self::assertIsString($options->get('docker.socket.path'));
    }

    public function testOptionsMock()
    {
        $options = OptionsMock::create();

        self::assertNull($options->get('foo.bar.baz'));
        self::assertNotNull($options->define('foo.bar.baz', 'top')->get('foo.bar.baz'));
        self::assertSame('top', $options->get('foo.bar.baz'));

        self::assertIsString($options->get('docker.socket.path'));
        $options->define('docker.socket.path', '/var/run/super-docker.sock');
        self::assertSame('/var/run/super-docker.sock', $options->get('docker.socket.path'));
    }

    public function testVerify()
    {
        $options = Options::create();
        self::assertNotNull($options->verify('', ''));
        self::assertNull($options->verify('', null));
    }

    public function testMockVerifyTypeDefinition()
    {
        // without types
        $optionsMock = OptionsMock::create();
        $optionsMock->define('foo', 'default', 1);
        self::assertSame('', $optionsMock->verify('foo', ''));

        // with types
        $optionsMock = OptionsMock::create(new Types());
        $optionsMock->define('foo', 'default', 1);
        self::assertNull($optionsMock->verify('foo', ''));
    }
}
