<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * @covers \Ktomk\Pipelines\ErrorCatcher
 */
class ErrorCatcherTest extends TestCase
{
    public function testCreation()
    {
        $catcher = new ErrorCatcher();

        self::assertInstanceOf('Ktomk\Pipelines\ErrorCatcher', $catcher);

        $catcher = ErrorCatcher::create();

        self::assertInstanceOf('Ktomk\Pipelines\ErrorCatcher', $catcher);
    }

    public function testEnd()
    {
        $catcher = new ErrorCatcher();
        self::assertFalse($catcher->end());
    }

    public function testEndWithError()
    {
        $catcher = new ErrorCatcher();
        trigger_error('test');
        self::assertTrue($catcher->end());
    }
}
