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
        $this->assertInstanceOf(
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
        $this->assertInstanceOf('Exception', $exception);
    }

    /**
     *
     */
    public function testMinimumCodeZero()
    {
        $this->assertNotNull(
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
        $this->assertNotNull(
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
     */
    public function testStatus()
    {
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('Foo Le Bar');
        $this->expectExceptionCode(22);

        StatusException::status(22, 'Foo Le Bar');
    }
}
