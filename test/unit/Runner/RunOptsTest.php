<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\TestCase;

/**
 * Class RunOptsTest
 *
 * @covers \Ktomk\Pipelines\Runner\RunOpts
 */
class RunOptsTest extends TestCase
{
    public function testCreation()
    {
        $opts = RunOpts::create('prefix', '');
        self::assertInstanceOf('Ktomk\Pipelines\Runner\RunOpts', $opts);

        return $opts;
    }

    /**
     * @depends testCreation
     *
     * @param RunOpts $opts
     */
    public function testPrefix(RunOpts $opts)
    {
        self::assertIsString($opts->getPrefix());
        $opts->setPrefix('foo');
        self::assertSame('foo', $opts->getPrefix());
    }

    /**
     * @depends testCreation
     *
     * @param RunOpts $opts
     */
    public function testGetOptions(RunOpts $opts)
    {
        self::assertNotNull($opts->getOption('docker.socket.path'));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("unknown option: 'foo.bar.baz'");
        $opts->getOption('foo.bar.baz');
    }

    /**
     * @depends testCreation
     *
     * @param RunOpts $opts
     */
    public function testGetBoolOption(RunOpts $opts)
    {
        self::assertTrue($opts->getBoolOption('docker.socket.path'));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("unknown option: 'foo.bar.baz'");
        $opts->getBoolOption('foo.bar.baz');
    }

    /**
     * by default options if n/a return null
     */
    public function testGetOptionWithoutOptions()
    {
        $opts = new RunOpts();
        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage('no options');
        $opts->getOption('foo.bar');
    }

    /**
     * @depends testCreation
     *
     * @param RunOpts $opts
     */
    public function testBinaryPackage(RunOpts $opts)
    {
        self::assertIsString($opts->getBinaryPackage());
        $opts->setBinaryPackage('foo');
        self::assertSame('foo', $opts->getBinaryPackage());
    }

    /**
     * @depends testCreation
     *
     * @param RunOpts $opts
     */
    public function testSteps(RunOpts $opts)
    {
        self::assertNull($opts->getSteps());
        $opts->setSteps('1,22,4');
        self::assertIsString($opts->getSteps());
    }

    /**
     * @depends testCreation
     *
     * @param RunOpts $opts
     */
    public function testNoManual(RunOpts $opts)
    {
        self::assertFalse($opts->isNoManual());
        $opts->setNoManual(true);
        self::assertTrue($opts->isNoManual());
    }

    /**
     * @depends testCreation
     *
     * @param RunOpts $opts
     */
    public function testUser(RunOpts $opts)
    {
        self::assertNull($opts->getUser());
        $opts->setUser('foo');
        self::assertSame('foo', $opts->getUser());
    }

    /**
     * @depends testCreation
     *
     * @param RunOpts $opts
     */
    public function testSsh(RunOpts $opts)
    {
        self::assertNull($opts->getSsh());
        $opts->setSsh(true);
        self::assertTrue($opts->getSsh());
    }
}
