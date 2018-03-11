<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\File\ParseException
 */
class ParseExceptionTest extends TestCase
{
    public function testCreation()
    {
        $exception = new ParseException('message string');
        $this->assertInstanceOf('Ktomk\Pipelines\File\ParseException', $exception);
        $this->assertInstanceOf('InvalidArgumentException', $exception);
        $this->assertInstanceOf('Exception', $exception);
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage message string
     */
    public function testThrowing()
    {
        ParseException::__('message string');
    }
}
