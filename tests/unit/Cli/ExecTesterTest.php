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
        $this->assertSame(0, $tester->pass(':', array()));

        $this->assertSame(array(':'), $tester->getDebugMessages());

        $tester->expect('capture', ':', 1);
        $this->assertSame(1, $tester->capture(':', array('capture')));

        $this->assertSame(array(':', ': capture'), $tester->getDebugMessages());
    }
}
