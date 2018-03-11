<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use PHPUnit\Framework\TestCase;

/**
 * Class StreamsTest
 *
 * @covers \Ktomk\Pipelines\Cli\Streams
 */
class StreamsTest extends TestCase
{
    private $closeHandles;

    protected function tearDown()
    {
        parent::tearDown();

        foreach ((array)$this->closeHandles as $resource) {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    public function testCreate()
    {
        $streams = Streams::create();
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Streams', $streams);
    }

    public function testHandleWrite()
    {
        $this->closeHandles[] = $capture = fopen('php://memory', 'w');
        $streams = new Streams(null, null, $capture);
        $streams->err("more");
        rewind($capture);
        $this->assertSame('more', stream_get_contents($capture));
    }

    public function testNullHandleWrite()
    {
        $this->closeHandles[] = $capture = fopen('php://memory', 'w');
        $streams = new Streams(null, null, $capture);
        $streams->out("something\n");
        rewind($capture);
        $this->assertSame('', stream_get_contents($capture));
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
        $streams("test");
    }

    public function testCopyHandle()
    {
        $this->expectOutputString("test\nme");
        $streams = new Streams(null, 'php://output');
        $other = new Streams();
        $other->copyHandle($streams, 1);
        $other->out("test\n");
        unset($other);
        $streams->out("me");
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage failed to open '/this-is-no-file-to/open/really' for reading
     */
    public function testExceptionOnOpening()
    {
        @new Streams('/this-is-no-file-to/open/really');
    }
}
