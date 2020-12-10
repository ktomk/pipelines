<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\ArgsException
 */
class ArgsExceptionTest extends TestCase
{
    /**
     */
    public function testGive()
    {
        $this->expectException('Ktomk\Pipelines\Cli\ArgsException');
        $this->expectExceptionMessage('test');

        throw new ArgsException('test');
    }
}
