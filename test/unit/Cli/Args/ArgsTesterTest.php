<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Args\ArgsTester
 */
class ArgsTesterTest extends TestCase
{
    public function testDefaultIsArray()
    {
        $argsTester = new ArgsTester();
        self::assertSame(array(), $argsTester->arguments);
    }
}
