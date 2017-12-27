<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\Cli;

use Ktomk\Pipelines\Cli\Proc;
use PHPUnit\Framework\TestCase;

/**
 * Class ProcTest
 *
 * @covers \Ktomk\Pipelines\Cli\Proc
 */
class ProcTest extends TestCase
{
    function testRun()
    {
        $command = 'pwd -P';

        $proc = new Proc($command);
        $status = $proc->run();
        $this->assertSame(0, $status);

        $outBuffer = $proc->getStandardOutput();
        $errorBuffer = $proc->getStandardError();

        $this->assertInternalType('string', $outBuffer);
        $this->assertEquals(
            getcwd() . "\n",
            $outBuffer,
            'compare pwd -P output against getcwd() return value'
        );

        $this->assertInternalType('string', $errorBuffer);
        $this->assertSame('', $errorBuffer);
    }

    function testFailRun()
    {
        $command = '/dev/null';
        $proc = new Proc($command);
        $status = $proc->run();
        $this->assertSame(126, $status);
        $errorBuffer = $proc->getStandardError();
        $this->assertRegExp(
            '~^.*/dev/null.*Permission denied.*\\n$~i',
            $errorBuffer
        );
    }
}
