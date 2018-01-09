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
        $proc = new Proc('');
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Proc', $proc);
    }

    function testGetStatus()
    {
        $proc = new Proc(':');
        $this->assertNull($proc->getStatus());
        $proc->run();
        $this->assertNotNull($proc->getStatus());
    }

    public function testSetStatus()
    {
        $proc = new Proc(':');
        $proc->setStatus(0);
        $this->assertSame(0, $proc->getStatus());
    }

    function testGetBuffer()
    {
        $proc = new Proc(':');
        $this->assertNull($proc->getStandardOutput());
        $this->assertNull($proc->getStandardError());
        $proc->run();
        $this->assertSame("", $proc->getStandardOutput());
        $this->assertSame("", $proc->getStandardError());
    }
}
