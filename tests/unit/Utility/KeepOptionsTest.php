<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Utility\KeepOptions
 */
class KeepOptionsTest extends TestCase
{
    public function testCreation()
    {
        $args = Args::create(array('test-util', '--error-keep'));
        $streams = new Streams(null, null, 'php://output');
        $keep = KeepOptions::bind($args, $streams);
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\KeepOptions', $keep);

        return $keep;
    }

    /**
     * @depends testCreation
     * @param KeepOptions $keep
     */
    public function testParse(KeepOptions $keep)
    {
        $this->assertNull($keep->run());
    }

    /**
     * @depends testCreation
     * @param KeepOptions $keep
     */
    public function testParseExclusivityErrorKeepAndKeep(KeepOptions $keep)
    {
        $this->expectOutputString("pipelines: --keep and --error-keep are exclusive\n");
        $args = Args::create(array('test-util', '--error-keep', '--keep'));
        $expected = array(1);
        $this->assertSame(
            $expected,
            $keep->parse($args)
        );
    }

    /**
     * @depends testCreation
     * @param KeepOptions $keep
     */
    public function testParseExclusivityKeepAndNoKeep(KeepOptions $keep)
    {
        $this->expectOutputString("pipelines: --keep and --no-keep are exclusive\n");
        $args = Args::create(array('test-util', '--no-keep', '--keep'));
        $expected = array(1);
        $this->assertSame(
            $expected,
            $keep->parse($args)
        );
    }

    /**
     * @depends testCreation
     * @param KeepOptions $keep
     */
    public function testParseExclusivityErrorKeepAndNoKeep(KeepOptions $keep)
    {
        $this->expectOutputString("pipelines: --error-keep and --no-keep are exclusive\n");
        $args = Args::create(array('test-util', '--no-keep', '--error-keep'));
        $expected = array(1);
        $this->assertSame(
            $expected,
            $keep->parse($args)
        );
    }
}
