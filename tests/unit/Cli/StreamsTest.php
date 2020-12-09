<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\TestCase;

/**
 * Class StreamsTest
 *
 * @covers \Ktomk\Pipelines\Cli\Streams
 */
class StreamsTest extends TestCase
{
    private $closeHandles;

    protected function doTearDown()
    {
        parent::doTearDown();

        foreach ((array)$this->closeHandles as $resource) {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    public function testCreate()
    {
        $streams = Streams::create();
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Streams', $streams);
    }

    public function testHandleWrite()
    {
        $this->closeHandles[] = $capture = fopen('php://memory', 'wb');
        $streams = new Streams(null, null, $capture);
        $streams->err('more');
        rewind($capture);
        self::assertSame('more', stream_get_contents($capture));
    }

    public function testNullHandleWrite()
    {
        $this->closeHandles[] = $capture = fopen('php://memory', 'wb');
        $streams = new Streams(null, null, $capture);
        $streams->out("something\n");
        rewind($capture);
        self::assertSame('', stream_get_contents($capture));
    }

    public function testCloseResourcesOnDestructor()
    {
        $this->expectOutputString("test\n");
        $streams = new Streams(null, 'php://output');
        $streams->out("test\n");
        $streams->__destruct(); # emulate unset($streams);
        $streams->out("me\n");
    }

    public function testInvocation()
    {
        $this->expectOutputString("test\n");
        $streams = new Streams(null, 'php://output');
        $streams('test');
    }

    public function testCopyHandle()
    {
        $this->expectOutputString("test\nme");
        $streams = new Streams(null, 'php://output');
        $other = new Streams();
        $other->copyHandle($streams, 1);
        $other->out("test\n");
        unset($other);
        $streams->out('me');
    }

    /**
     */
    public function testExceptionOnOpening()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('failed to open \'/this-is-no-file-to/open/really\' for reading');

        @new Streams('/this-is-no-file-to/open/really');
    }
}
