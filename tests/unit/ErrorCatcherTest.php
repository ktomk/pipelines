<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\ErrorCatcher
 */
class ErrorCatcherTest extends TestCase
{
    public function testCreation()
    {
        $catcher = new ErrorCatcher();

        $this->assertInstanceOf('Ktomk\Pipelines\ErrorCatcher', $catcher);

        $catcher = ErrorCatcher::create();

        $this->assertInstanceOf('Ktomk\Pipelines\ErrorCatcher', $catcher);
    }

    public function testEnd()
    {
        $catcher = new ErrorCatcher();
        $this->assertFalse($catcher->end());
    }

    public function testEndWithError()
    {
        $catcher = new ErrorCatcher();
        trigger_error('test');
        $this->assertTrue($catcher->end());
    }
}
