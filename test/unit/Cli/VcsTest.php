<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\TestCase;

/**
 * Class VcsTest
 *
 * @covers \Ktomk\Pipelines\Cli\Vcs
 */
class VcsTest extends TestCase
{
    public function testCreation()
    {
        $exec = new ExecTester($this);
        $vcs = new Vcs($exec);
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Vcs', $vcs);
    }

    public function testGetTopLevelDirectory()
    {
        $expected = __DIR__;

        $exec = new ExecTester($this);
        $exec->expect('capture', 'git', $expected . "\n");
        $vcs = new Vcs($exec);
        $actual = $vcs->getTopLevelDirectory();
        self::assertIsString($actual);
        self::assertSame($expected, $actual);
    }
}
