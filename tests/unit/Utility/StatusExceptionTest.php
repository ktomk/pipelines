<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use PHPUnit\Framework\TestCase;

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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Code must be integer in range from 0 to 255, -1 given
     */
    public function testMinimumCodeZeroOneOff()
    {
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Code must be integer in range from 0 to 255, 256 given
     */
    public function testMaximumCode255OneOff()
    {
        throw new StatusException('', 256);
    }

    /**
     * @expectedException \Ktomk\Pipelines\Utility\StatusException
     * @expectedExceptionMessage Foo Le Bar
     * @expectedExceptionCode 22
     */
    public function testStatus()
    {
        StatusException::status(22, 'Foo Le Bar');
    }
}
