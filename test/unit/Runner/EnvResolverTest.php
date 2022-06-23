<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\TestCase;

/**
 * Class EnvResolverTest
 *
 * @covers \Ktomk\Pipelines\Runner\EnvResolver
 */
class EnvResolverTest extends TestCase
{
    public function testCreation()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        self::assertInstanceOf('Ktomk\Pipelines\Runner\EnvResolver', $resolver);
    }

    public function testGetValue()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        self::assertNull($resolver->getValue('UID'));
    }

    public function testAddArgs()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        $args = new Args(array(
            '-env', 'OVERRIDE=red', '-e', 'OVERRIDE=green',
            '--env-file', 'test/data/env/test.env', '-e', 'UID',
            '-e', 'MOCHA',
        ));
        $resolver->addArguments($args);
        self::assertSame('annabelle', $resolver->getValue('USER'));
        self::assertSame('green', $resolver->getValue('OVERRIDE'));
        self::assertNull($resolver->getValue('DECAFFEINATED'));
        self::assertSame('1000', $resolver->getValue('UID'), 'exported variable');
        self::assertNull($resolver->getValue('MOCHA'), 'unset, file override');
    }

    public function testAddDefinitions()
    {
        $definitions = array(
            'DOCKER_ID_USER',
            'DOCKER_ID_PASSWORD',
            'DOCKER_ID_EMAIL=foo@example.com',
        );
        $resolver = new EnvResolver(array('DOCKER_ID_USER' => 'electra'));
        Lib::iterEach(array($resolver, 'addDefinition'), $definitions);

        $actual = $resolver->getValue('DOCKER_ID_PASSWORD');
        self::assertNull($actual, 'non-existing environment variable');

        $actual = $resolver->getValue('DOCKER_ID_USER');
        self::assertSame('electra', $actual, 'existing variable');

        $actual = $resolver->getValue('DOCKER_ID_EMAIL');
        self::assertSame('foo@example.com', $actual, 'set variable');
    }

    public function testAddFile()
    {
        $resolver = new EnvResolver(array('DOCKER_ID_USER' => 'electra'));
        $resolver->addFile(__DIR__ . '/../../../.env.dist');

        $actual = $resolver->getValue('DOCKER_ID_PASSWORD');
        self::assertNull($actual, 'non-existing environment variable');

        $actual = $resolver->getValue('DOCKER_ID_USER');
        self::assertSame('electra', $actual, 'existing variable');
    }

    /**
     */
    public function testAddInvalidFile()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('File read error: \'/abc/xyz/nada-kar-la-da\'');

        $resolver = new EnvResolver(array('UID' => '1000'));
        @$resolver->addFile('/abc/xyz/nada-kar-la-da');
    }

    public function testAddFileIfExists1()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        $resolver->addFileIfExists('/abc/xyz/nada-kar-la-da');
        self::assertNull($resolver->getValue('UID'));
    }

    public function testAddFileIfExists2()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        $resolver->addFileIfExists(__DIR__ . '/../../data/env/test.env');
        self::assertNull($resolver->getValue('UID'));
    }

    /**
     */
    public function testInvalidDefinition()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Environment variable error: $');

        $resolver = new EnvResolver(array('UID' => '1000'));
        $resolver->addDefinition('$');
    }

    public function testResolveString()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));

        $actual = $resolver->resolveString('UID');
        self::assertSame('UID', $actual, 'no variable');

        $actual = $resolver->resolveString('$UID');
        self::assertSame('', $actual, 'undefined variable');

        $resolver->addDefinition('UID');

        $actual = $resolver->resolveString('$UID');
        self::assertSame('1000', $actual, 'defined variable');
    }

    public function testStringResolver()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        $resolver->addDefinition('UID');
        $resolver->addDefinition('GOO_711=55');
        $resolver->addDefinition('G=TOP');
        $input = array(
            'foo' => 'UID',
            'bar' => '$BAR',
            'baz' => 'baz',
            'uid' => '$UID',
            'goo' => '$GOO_711',
            'g' => '$G',
        );
        $expected = array(
            'foo' => 'UID',
            'bar' => '',
            'baz' => 'baz',
            'uid' => '1000',
            'goo' => '55',
            'g' => 'TOP',
        );

        self::assertIsCallable($resolver);

        # string mode
        $actual = array_map($resolver, $input);
        self::assertSame($expected, $actual);

        # array mode
        $actual = $resolver($input);
        self::assertSame($expected, $actual);
    }
}
