<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Args\Collector
 */
class CollectorTest extends TestCase
{
    public function testCreation()
    {
        $args = new Args(array('test'));
        $collector = new Collector($args);
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Args\Collector', $collector);
    }

    /**
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     */
    public function testCollecting()
    {
        $args = new Args(array('test', '--foo', 'bar', '-f', 'bar'));
        $collector = new Collector($args);
        $collector->collect(array('f'));
        $options = new OptionIterator($collector);
        foreach ($options as $index => $option)
        {
            $this->assertSame($index, 0);
            $this->assertSame('-f', $option);
            $this->assertSame('bar', $options->getArgument());
        }
    }

    public function testGetArgs()
    {
        $collector = new Collector($args = new ArgsTester());
        $expected = array();
        $actual = $collector->getArgs();
        $this->assertSame($expected, $actual);
    }
}
