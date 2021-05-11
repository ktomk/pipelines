<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\TestCase;

/**
 * Class StatusExceptionTest
 *
 * @covers \Ktomk\Pipelines\Utility\StatusException
 */
class StatusExceptionTest extends TestCase
{
    /**
     * @return StatusException
     */
    public function testCreation()
    {
        $actual = new StatusException('this is what it counts for', 1);
        self::assertInstanceOf(
            'Ktomk\Pipelines\Utility\StatusException',
            $actual
        );

        return $actual;
    }

    /**
     * @depends testCreation
     *
     * @param StatusException $exception
     */
    public function testThrowable(StatusException $exception)
    {
        self::assertInstanceOf('Exception', $exception);
    }

    /**
     *
     */
    public function testMinimumCodeZero()
    {
        self::assertNotNull(
            new StatusException('', 0)
        );
    }

    /**
     */
    public function testMinimumCodeZeroOneOff()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Code must be integer in range from 0 to 255, -1 given');

        new StatusException('', -1);
    }

    /**
     *
     */
    public function testMaximumCode255()
    {
        self::assertNotNull(
            new StatusException('', 255)
        );
    }

    /**
     * @throws StatusException (never)
     */
    public function testMaximumCode255OneOff()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Code must be integer in range from 0 to 255, 256 given');

        throw new StatusException('', 256);
    }

    /**
     * This is less a test but more a demonstration
     *
     * @throws StatusException
     */
    public function testStatusThrowUsage()
    {
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('Foo Le Bar');
        $this->expectExceptionCode(22);

        throw new StatusException('Foo Le Bar', 22);
    }

    /**
     * @throws StatusException
     */
    public function testFatalThrow()
    {
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('very bad');
        $this->expectExceptionCode(1);

        StatusException::fatal('very bad');
    }

    /**
     * @throws StatusException
     */
    public function testBareFatalThrow()
    {
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('fatal abort');
        $this->expectExceptionCode(1);

        StatusException::fatal();
    }

    /**
     * @throws StatusException
     */
    public function testOkThrow()
    {
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('');
        $this->expectExceptionCode(0);

        StatusException::ok();
    }
}
