<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\ArgsException
 */
class ArgsExceptionTest extends TestCase
{
    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage test
     */
    public function testGive()
    {
        ArgsException::__('test');
    }
}
