<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\File\ParseException
 */
class ParseExceptionTest extends TestCase
{
    /**
     * @return ParseException
     */
    public function testCreation()
    {
        $exception = new ParseException('message string');
        $this->assertInstanceOf('Ktomk\Pipelines\File\ParseException', $exception);
        $this->assertInstanceOf('InvalidArgumentException', $exception);
        $this->assertInstanceOf('Exception', $exception);

        return $exception;
    }

    /**
     * @param ParseException $exception
     * @depends testCreation
     */
    public function testGetParseMessage(ParseException $exception)
    {
        $this->assertNull($exception->getParseMessage());
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
