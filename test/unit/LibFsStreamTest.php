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

    public function provideFdToPhpExpectations()
    {
        return array(
            'not-converting-empty' => array('', ''),
            'device-file-file-descriptor-standard-input' => array('/dev/fd/0', 'php://fd/0'),
            'device-file-file-descriptor-standard-output' => array('/dev/fd/1', 'php://fd/1'),
            'device-file-file-descriptor-standard-error' => array('/dev/fd/2', 'php://fd/2'),
            'linux-proc-self-standard-input' => array('/proc/self/fd/0', 'php://fd/0'),
            'linux-proc-self-standard-output' => array('/proc/self/fd/1', 'php://fd/1'),
            'linux-proc-self-standard-error' => array('/proc/self/fd/3', 'php://fd/3'),
            'linux-proc-self-bash-process-substitution' => array('/proc/self/fd/63', 'php://fd/63'),
            'no-path-normalization' => array('//dev/fd/1', '//dev/fd/1'),
            'no-relative-paths' => array('dev/fd/1', 'dev/fd/1'),
            'regression: zero-prefixed-numbers'=> array( '/dev/fd/01', '/dev/fd/01'),
            'regression: trailing bytes'=> array( '/dev/fd/1000   ', '/dev/fd/1000   '),
        );
    }

    /**
     * @dataProvider provideFdToPhpExpectations
     *
     * @param string $path
     * @param string $expected
     *
     * @return void
     */
    public function testFdToPhp($path, $expected)
    {
        self::assertSame($expected, LibFsStream::fdToPhp($path));
    }
}
