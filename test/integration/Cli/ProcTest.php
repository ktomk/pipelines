<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\Cli;

use Ktomk\Pipelines\Cli\Proc;
use Ktomk\Pipelines\TestCase;

/**
 * Class ProcTest
 *
 * @coversNothing
 */
class ProcTest extends TestCase
{
    public function testRun()
    {
        $command = 'pwd -P';

        $proc = new Proc($command);
        $status = $proc->run();
        self::assertSame(0, $status);

        $outBuffer = $proc->getStandardOutput();
        $errorBuffer = $proc->getStandardError();

        self::assertIsString($outBuffer);
        self::assertSame(
            getcwd() . "\n",
            $outBuffer,
            'compare pwd -P output against getcwd() return value'
        );

        self::assertIsString($errorBuffer);
        self::assertSame('', $errorBuffer);
    }

    public function testFailRun()
    {
        $command = '/dev/null';
        $proc = new Proc($command);
        $status = $proc->run();
        self::assertSame(126, $status);
        $errorBuffer = $proc->getStandardError();
        self::assertMatchesRegularExpression(
            '~^.*/dev/null.*Permission denied.*\\n$~i',
            $errorBuffer
        );
    }
}
