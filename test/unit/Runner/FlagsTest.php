<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Utility\CacheOptions;
use Ktomk\Pipelines\Utility\KeepOptions;

/**
 * @covers \Ktomk\Pipelines\Runner\Flags
 */
class FlagsTest extends TestCase
{
    public function testCreation()
    {
        $flags = new Flags();
        self::assertInstanceOf('\Ktomk\Pipelines\Runner\Flags', $flags);
    }

    public function provideCreations()
    {
        return array(
            array(array(), 'copy'),
            array(array('--keep'), 'copy'),
            array(array('--error-keep'), 'copy'),
        );
    }

    /**
     * @dataProvider provideCreations
     *
     * @param array $args
     * @param string $deploy
     *
     * @throws \Ktomk\Pipelines\Utility\StatusException
     */
    public function testCreationForUtility(array $args, $deploy)
    {
        $args = new Args($args);
        $keep = new KeepOptions($args);
        $cache = new CacheOptions($args);
        $flags = Flags::createForUtility($keep->run(), $deploy, $cache);
        self::assertInstanceOf('\Ktomk\Pipelines\Runner\Flags', $flags);
    }

    public function testReuseContainer()
    {
        $value = $this->flagsValue($flags = new Flags());
        $reuseContainer
            = ($value & Flags::FLAG_KEEP_ON_ERROR)
            || !($value & (Flags::FLAG_DOCKER_KILL | Flags::FLAG_DOCKER_REMOVE));
        self::assertSame($reuseContainer, $flags->reuseContainer());
    }

    public function testDeployCopy()
    {
        $value = $this->flagsValue($flags = new Flags());
        $copy = (bool)($value & Flags::FLAG_DEPLOY_COPY);
        self::assertSame($copy, $flags->deployCopy());
    }

    public function testFlgHas()
    {
        $flags = new Flags();
        self::assertTrue($flags->flgHas($flags::FLAG_DOCKER_KILL));
        self::assertTrue($flags->flgHas($flags::FLAG_DOCKER_REMOVE));
        self::assertTrue($flags->flgHas($flags::FLAG_DOCKER_KILL | $flags::FLAG_DOCKER_REMOVE));
    }

    public function testKeep()
    {
        $value = $this->flagsValue($flags = new Flags());
        $keep = !($value & (Flags::FLAG_DOCKER_KILL | Flags::FLAG_DOCKER_REMOVE));
        self::assertSame($keep, $flags->keep());
    }

    public function testKeepOnError()
    {
        $value = $this->flagsValue($flags = new Flags());
        $keep = (bool)($value & Flags::FLAG_KEEP_ON_ERROR);
        self::assertSame($keep, $flags->keepOnError());
    }

    public function testKillContainer()
    {
        $value = $this->flagsValue($flags = new Flags());
        $kill = (bool)($value & Flags::FLAG_DOCKER_KILL);
        self::assertSame($kill, $flags->killContainer());
    }

    public function testMountSocket()
    {
        $value = $this->flagsValue($flags = new Flags());
        $socket = (bool)($value & Flags::FLAG_SOCKET);
        self::assertSame($socket, $flags->useDockerSocket());
    }

    public function testRemoveContainer()
    {
        $value = $this->flagsValue($flags = new Flags());
        $remove = (bool)($value & Flags::FLAG_DOCKER_REMOVE);
        self::assertSame($remove, $flags->removeContainer());
    }

    public function testNoCache()
    {
        $flags = new Flags();
        self::assertFalse($flags->noCache());
    }

    private function flagsValue(Flags $flags = null)
    {
        null === $flags && $flags = new Flags();

        return $flags->memory;
    }
}
