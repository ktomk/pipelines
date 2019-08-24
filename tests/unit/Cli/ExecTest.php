<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Exec
 */
class ExecTest extends TestCase
{
    public function testCreation()
    {
        $exec = new Exec();
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Exec', $exec);
    }

    public function testPass()
    {
        $exec = new Exec();
        $actual = $exec->pass(':', array());
        $this->assertSame(0, $actual);
    }

    public function testCapture()
    {
        $exec = new Exec();
        $actual = $exec->capture(':', array());
        $this->assertSame(0, $actual);
    }

    public function testDebugger()
    {
        $lines = array();
        $exec = new Exec(function($message) use (&$lines) {
            $lines[] = $message;
        });
        $actual = $exec->capture(':', array());
        $this->assertSame(0, $actual);
        $this->assertArrayHasKey(0, $lines);
        $this->assertSame(':', $lines[0]);
        $this->assertCount(2, $lines);
    }

    public function testDeactivation()
    {
        $exec = new Exec();
        $exec->setActive(false);
        $this->assertSame(0, $exec->pass('/dev/null', array()));
        $this->assertSame(0, $exec->capture('/dev/null', array()));
    }
}
