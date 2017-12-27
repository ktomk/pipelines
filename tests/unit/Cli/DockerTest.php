<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\Cli\Docker
 */
class DockerTest extends UnitTestCase
{
    function testCommandDetectionAndVersionPaths()
    {
        $procFail = $this->createMock('Ktomk\Pipelines\Cli\Proc');

        $procGood = $this->createMock('Ktomk\Pipelines\Cli\Proc');
        $procGood->method('getStatus')->willReturn(0);
        $procGood->method('getStandardOutput')->willReturnCallback(
            function() use (&$procGoodOutput) {
                return $procGoodOutput;
            }
        );

        /** @var MockObject|Exec $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $results = array(
            $procFail,
            $procFail,
            $procGood,
            $procFail,
            $procGood,
            $procGood,
            $procGood,
            $procGood
        );
        $exec->method('capture')->willReturnCallback(
            function ($command, $args, &$out, &$err = null) use ($results) {
                static $call = -1;
                $call++;
                /** @var Proc $proc */
                $proc = $results[$call];
                $out = $proc->getStandardOutput();
                $err = $proc->getStandardError();
                return $proc->getStatus();
            }
        );

        $docker = new Docker($exec);
        $this->assertFalse($docker->hasCommand());
        $this->assertNull($docker->getVersion());

        $this->assertNull($docker->getVersion());
        $this->assertSame('0.0.0-err', @$docker->getVersion());

        $procGoodOutput = "17.09.1-ce\n";
        $this->assertSame('17.09.1-ce', $docker->getVersion());
    }
}
