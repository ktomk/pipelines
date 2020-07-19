<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\TestCase;

/**
 * Class ExecTesterTest
 *
 * @covers \Ktomk\Pipelines\Cli\ExecTester
 *
 * @package Ktomk\Pipelines\Cli
 */
class ExecTesterTest extends TestCase
{
    public function testDebugMessages()
    {
        $tester = new ExecTester($this);
        $this->addToAssertionCount(1);

        $tester->expect('pass', ':');
        self::assertSame(0, $tester->pass(':', array()));

        self::assertSame(array(':'), $tester->getDebugMessages());

        $tester->expect('capture', ':', 1);
        self::assertSame(1, $tester->capture(':', array('capture')));

        self::assertSame(array(':', ': capture'), $tester->getDebugMessages());
    }
}
