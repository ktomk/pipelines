<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value\Env;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Value\Env\EnvVar
 */
class EnvVarTest extends TestCase
{
    public function provideValidEnvVar()
    {
        return array(
            array('HOME=/home/user', array('HOME', '/home/user')),
            array('NULL=', array('NULL', '')),
            array('HOME', array('HOME', null)),
        );
    }

    /**
     * @dataProvider provideValidEnvVar
     *
     * @param string $envVar
     * @param array $_
     *
     * @return void
     */
    public function testCreation($envVar, array $_)
    {
        $var = new EnvVar($envVar);
        self::assertNotNull($var);
    }

    public function testGetName()
    {
        $var = new EnvVar('FOO=');
        self::assertSame('FOO', $var->getName());
    }

    public function testGetValue()
    {
        $var = new EnvVar('FOO=');
        self::assertSame('', $var->getValue());
    }

    public function testGetValueThrowsIfNoValue()
    {
        $var = new EnvVar('FOO');
        $this->expectException('BadFunctionCallException');
        $this->expectExceptionMessage('Environment variable FOO has no value');
        !$var->getValue();
    }

    public function testTryValue()
    {
        $var = new EnvVar('FOO');
        self::assertNull($var->tryValue());

        $var = new EnvVar('FOO=BAR');
        self::assertSame('BAR', $var->tryValue());
    }

    public function testInvalidNameThrows()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Environment variable error: \\000\\ \\\\=NULL");
        $_ = new EnvVar("\0 \\=NULL");
    }

    /**
     * @dataProvider provideValidEnvVar
     *
     * @param $envVar
     * @param array $expected
     *
     * @return void
     */
    public function testGetPair($envVar, array $expected)
    {
        $var = new EnvVar($envVar);
        self::assertSame($expected, $var->getPair(), "EnvVar: ${envVar}");
        self::assertSame($envVar, (string)$var, "EnvVar (string): ${envVar}");
    }

    public function testNullByteInValueThrows()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Environment variable error: HOME=/home\\000/user");
        $_ = new EnvVar("HOME=/home\0/user");
    }

    public function provideInvalidEnvVar()
    {
        return array(
            'empty' => array(''),
            'space-in-name' => array('FOO BAR=le lax'),
        );
    }

    /**
     * @dataProvider provideInvalidEnvVar
     *
     * @param string $envVar
     *
     * @return void
     */
    public function testThrowingEnvVars($envVar)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Environment variable error: ');
        $_ = new EnvVar($envVar);
    }
}
