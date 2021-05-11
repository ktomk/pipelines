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
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Args\Collector', $collector);
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
        foreach ($options as $index => $option) {
            self::assertSame($index, 0);
            self::assertSame('-f', $option);
            self::assertSame('bar', $options->getArgument());
        }
    }

    public function testGetArgs()
    {
        $collector = new Collector(new ArgsTester());
        $expected = array();
        $actual = $collector->getArgs();
        self::assertSame($expected, $actual);
    }
}
