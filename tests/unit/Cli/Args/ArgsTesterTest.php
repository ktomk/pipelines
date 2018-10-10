<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Args\ArgsTester
 */
class ArgsTesterTest extends TestCase
{
    public function testDefaultIsArray()
    {
        $argsTester = new ArgsTester();
        $this->assertSame(array(), $argsTester->arguments);
    }
}
