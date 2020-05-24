<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Proc
 */
class ProcTest extends TestCase
{
    public function testCreation()
    {
        $proc = new Proc('');
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Proc', $proc);
    }

    public function testGetStatus()
    {
        $proc = new Proc(':');
        $this->assertNull($proc->getStatus());
        $proc->run();
        $this->assertNotNull($proc->getStatus());
    }

    public function testSetStatus()
    {
        $proc = new Proc(':');
        $this->assertNull($proc->getStatus());
        $proc->setStatus(0);
        $this->assertSame(0, $proc->getStatus());
    }

    public function testGetBuffer()
    {
        $proc = new Proc(':');
        $this->assertSame('', $proc->getStandardOutput());
        $this->assertSame('', $proc->getStandardError());
        $proc->run();
        $this->assertSame('', $proc->getStandardOutput());
        $this->assertSame('', $proc->getStandardError());
    }

    /**
     * PHP_BINARY available since 5.4.0
     *
     * @requires PHP 5.4.0
     */
    public function testRun()
    {
        $proc = new Proc(PHP_BINARY . ' -v');
        $this->assertSame(0, $proc->run());
    }
}
