<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * Class LibFsStreamTest
 *
 * @package Ktomk\Pipelines
 * @covers \Ktomk\Pipelines\LibFsStream
 */
class LibFsStreamTest extends TestCase
{
    public function testIsReadable()
    {
        self::assertTrue(LibFsStream::isReadable(__FILE__), 'standard file');
        self::assertTrue(LibFsStream::isReadable('php://stdin'), 'php stream: php://stdin');

        self::assertFalse(LibFsStream::isReadable(null), 'non-string so that resources seem filtered');
        self::assertFalse(LibFsStream::isReadable('http:'), 'php stream: http:');
        self::assertFalse(LibFsStream::isReadable('http://'), 'php stream: http://');
        self::assertFalse(LibFsStream::isReadable('http://ktomk.github.io/'), 'php stream: http://...');
    }

    public function testIsUri()
    {
        self::assertTrue(LibFsStream::isUri('data://text/plain,'));
        self::assertFalse(LibFsStream::isUri(__FILE__));
    }

    public function testMapFile()
    {
        self::assertSame(__FILE__, LibFsStream::mapFile(__FILE__));
        self::assertSame('php://stdin', LibFsStream::mapFile('-'));
        self::assertSame('php://stdin', LibFsStream::mapFile('-', 'php://stdin'));
        self::assertNotSame('php://stdin', LibFsStream::mapFile('-', null));
        self::assertSame('php://stdin', LibFsStream::mapFile('php://stdin', null));
        self::assertSame('php://fd/11', LibFsStream::mapFile('/proc/self/fd/11'));
    }
}
