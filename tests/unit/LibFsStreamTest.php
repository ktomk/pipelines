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
        $this->assertTrue(LibFsStream::isReadable(__FILE__), 'standard file');
        $this->assertTrue(LibFsStream::isReadable('php://stdin'), 'php stream: php://stdin');

        $this->assertFalse(LibFsStream::isReadable(null), 'non-string so that resources seem filtered');
        $this->assertFalse(LibFsStream::isReadable('http:'), 'php stream: http:');
        $this->assertFalse(LibFsStream::isReadable('http://'), 'php stream: http://');
        $this->assertFalse(LibFsStream::isReadable('http://ktomk.github.io/'), 'php stream: http://...');
    }

    public function testIsUri()
    {
        $this->assertTrue(LibFsStream::isUri('data://text/plain,'));
        $this->assertFalse(LibFsStream::isUri(__FILE__));
    }

    public function testMapFile()
    {
        $this->assertSame(__FILE__, LibFsStream::mapFile(__FILE__));
        $this->assertSame('php://stdin', LibFsStream::mapFile('-'));
        $this->assertSame('php://stdin', LibFsStream::mapFile('-', 'php://stdin'));
        $this->assertNotSame('php://stdin', LibFsStream::mapFile('-', null));
        $this->assertSame('php://stdin', LibFsStream::mapFile('php://stdin', null));
        $this->assertSame('php://fd/11', LibFsStream::mapFile('/proc/self/fd/11'));
    }
}
