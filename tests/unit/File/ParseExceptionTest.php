<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\TestCase;

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
        self::assertIsString($exception->getParseMessage());
    }

    /**
     */
    public function testThrowing()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('message string');

        throw new ParseException('message string');
    }
}
