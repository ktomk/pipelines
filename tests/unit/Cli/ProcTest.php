<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Proc
 */
class ProcTest extends TestCase
{
    function testCreation()
    {
        $proc = Proc::create('', array());
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Proc', $proc);
    }

    function testGetStatus()
    {
        $proc = Proc::create(':', array());
        $this->assertNull($proc->getStatus());
        $proc->run();
        $this->assertNotNull($proc->getStatus());
    }

    public function testSetStatus()
    {
        $proc = Proc::create(':', array());
        $proc->setStatus(0);
        $this->assertSame(0, $proc->getStatus());
    }

    function testGetBuffer()
    {
        $proc = Proc::create(':', array());
        $this->assertNull($proc->getStandardOutput());
        $this->assertNull($proc->getStandardError());
        $proc->run();
        $this->assertNotNull($proc->getStandardOutput());
        $this->assertNotNull($proc->getStandardError());
    }
}
