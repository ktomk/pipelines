<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Runner\Flags
 */
class FlagsTest extends TestCase
{
    public function testCreation()
    {
        $flags = new Flags();
        $this->assertInstanceOf('\Ktomk\Pipelines\Runner\Flags', $flags);
    }

    public function testReuseContainer()
    {
        $value = $this->flagsValue($flags = new Flags());
        $reuseContainer =
            ($value & Flags::FLAG_KEEP_ON_ERROR)
            || !($value & (Flags::FLAG_DOCKER_KILL | Flags::FLAG_DOCKER_REMOVE));
        $this->assertSame($reuseContainer, $flags->reuseContainer());
    }

    public function testDeployCopy()
    {
        $value = $this->flagsValue($flags = new Flags());
        $copy = (bool)($value & Flags::FLAG_DEPLOY_COPY);
        $this->assertSame($copy, $flags->deployCopy());
    }

    public function testFlgHas()
    {
        $flags = new Flags();
        $this->assertTrue($flags->flgHas($flags::FLAG_DOCKER_KILL));
        $this->assertTrue($flags->flgHas($flags::FLAG_DOCKER_REMOVE));
        $this->assertTrue($flags->flgHas($flags::FLAG_DOCKER_KILL | $flags::FLAG_DOCKER_REMOVE));
    }

    public function testKeep()
    {
        $value = $this->flagsValue($flags = new Flags());
        $keep = !($value & (Flags::FLAG_DOCKER_KILL | Flags::FLAG_DOCKER_REMOVE));
        $this->assertSame($keep, $flags->keep());
    }

    public function testKeepOnError()
    {
        $value = $this->flagsValue($flags = new Flags());
        $keep = (bool)($value & Flags::FLAG_KEEP_ON_ERROR);
        $this->assertSame($keep, $flags->keepOnError());
    }

    public function testKillContainer()
    {
        $value = $this->flagsValue($flags = new Flags());
        $kill = (bool)($value & Flags::FLAG_DOCKER_KILL);
        $this->assertSame($kill, $flags->killContainer());
    }

    public function testMountSocket()
    {
        $value = $this->flagsValue($flags = new Flags());
        $socket = (bool)($value & Flags::FLAG_SOCKET);
        $this->assertSame($socket, $flags->useDockerSocket());
    }

    public function testRemoveContainer()
    {
        $value = $this->flagsValue($flags = new Flags());
        $remove = (bool)($value & Flags::FLAG_DOCKER_REMOVE);
        $this->assertSame($remove, $flags->removeContainer());
    }

    private function flagsValue(Flags $flags = null)
    {
        null === $flags && $flags = new Flags();

        return $flags->memory;
    }
}
