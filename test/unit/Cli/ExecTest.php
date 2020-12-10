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
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Exec', $exec);
    }

    public function testPass()
    {
        $exec = new Exec();
        $actual = $exec->pass(':', array());
        self::assertSame(0, $actual);
    }

    public function testCapture()
    {
        $exec = new Exec();
        $actual = $exec->capture(':', array());
        self::assertSame(0, $actual);
    }

    public function testDebugger()
    {
        $lines = array();
        $exec = new Exec(function ($message) use (&$lines) {
            $lines[] = $message;
        });
        $actual = $exec->capture(':', array());
        self::assertSame(0, $actual);
        self::assertArrayHasKey(0, $lines);
        self::assertSame(':', $lines[0]);
        self::assertCount(2, $lines);
    }

    public function testDeactivation()
    {
        $exec = new Exec();
        $exec->setActive(false);
        self::assertSame(0, $exec->pass('/dev/null', array()));
        self::assertSame(0, $exec->capture('/dev/null', array()));
    }
}
