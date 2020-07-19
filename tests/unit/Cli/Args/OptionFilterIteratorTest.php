<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Args\OptionFilterIterator
 */
class OptionFilterIteratorTest extends TestCase
{
    public function testCreation()
    {
        $args = new Args(array('cmd', '--foo', '-f', '--bar', '-b'));
        $filter = new OptionFilterIterator($args, array());
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Args\OptionFilterIterator', $filter);
    }

    public function testEmptyFiltering()
    {
        $args = new Args(array('cmd', '--foo', '-f', '--bar', '-b'));
        $filter = new OptionFilterIterator($args, null);
        self::assertSame(0, iterator_count($filter));
    }

    public function testSingleFiltering()
    {
        $args = new Args(array('cmd', '--foo', '-f', '--bar', '-b'));
        $filter = new OptionFilterIterator($args, array('f', 'bar'));
        self::assertSame(2, iterator_count($filter));
    }

    public function provideInvalidOptions()
    {
        return array(
            array(''),
            array(' '),
            array('-'),
            array('foo bar'),
            array('-5000'),
            array('?'),
            array('!'),
            array('='),
            array('.'),
        );
    }

    /**
     * @dataProvider provideInvalidOptions
     *
     * @param string $option (invalid one)
     */
    public function testInvalidOptions($option)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('invalid option -');

        $args = new Args(array('cmd', '--foo', '-f', '--bar', '-b'));
        new OptionFilterIterator($args, $option);
    }

    public function testGetOptionDescription()
    {
        $args = new Args(array('cmd', '--foo', '-f'));
        $iterator = new OptionFilterIterator($args, array('foo', 'f'));
        $expected = '--foo, -f';
        $actual = $iterator->getOptionDescription();
        self::assertSame($expected, $actual);
    }

    public function testGetArguments()
    {
        $args = new Args(
            array('cmd', '--foo', 'blue', '-f', 'red', '--', '-f', 'silver')
        );
        $iterator = new OptionFilterIterator($args, array('f', 'foo'));
        $expected = array('blue', 'red');
        $actual = $iterator->getArguments();
        self::assertSame($expected, $actual);
    }
}
