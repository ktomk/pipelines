<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\TestCase;
use UnexpectedValueException;

/**
 * Class ExceptionHandlerTest
 *
 * @covers \Ktomk\Pipelines\Utility\ExceptionHandler
 */
class ExceptionHandlerTest extends TestCase
{
    public function testCreation()
    {
        $streams = new Streams();
        $handler = new ExceptionHandler($streams, new Help($streams), true);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\ExceptionHandler', $handler);

        return $handler;
    }

    /**
     * @param ExceptionHandler $handler
     * @depends testCreation
     */
    public function testHandle(ExceptionHandler $handler)
    {
        $runnable = RunnableTester::create();
        self::assertNull($handler->handle($runnable));
    }

    /**
     * @param ExceptionHandler $handler
     * @depends testCreation
     */
    public function testHandleException(ExceptionHandler $handler)
    {
        $runnable = RunnableTester::create(
            function () {
                throw new UnexpectedValueException('handle this!');
            }
        );
        self::assertSame(2, $handler->handle($runnable));
    }

    /**
     * @param ExceptionHandler $handler
     * @depends testCreation
     */
    public function testHandleArgsException(ExceptionHandler $handler)
    {
        $runnable = RunnableTester::create(
            function () {
                throw new ArgsException('handle this!', 22);
            }
        );
        self::assertSame(22, $handler->handle($runnable));
    }

    public function testHandleStatus()
    {
        self::assertSame(
            0,
            $this->createPartialMock('Ktomk\Pipelines\Utility\ExceptionHandler', array())->handleStatus(
                $this->getMockForAbstractClass('Ktomk\Pipelines\Utility\StatusRunnable')
            )
        );
    }
}
