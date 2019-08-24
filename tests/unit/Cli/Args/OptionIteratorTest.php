<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\TestCase;

/**
 * Iterate over options, with argument look-ahead
 *
 * @covers \Ktomk\Pipelines\Cli\Args\OptionIterator
 */
class OptionIteratorTest extends TestCase
{
    public function testCreation()
    {
        $iterator = $this->iter();

        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Args\OptionIterator', $iterator);
    }

    public function testCurrent()
    {
        $iterator = $this->iter();
        $this->assertSame('--foo', $iterator->current());
        $iterator->rewind();
        $this->assertSame('--foo', $iterator->current());
    }

    public function testInvalidateCurrent()
    {
        $args = new ArgsTester;
        $args->arguments = array('--foo', 'bar', '-f', 'b', '--', 'parameter');
        $iterator = new OptionIterator($args);
        $this->assertSame('--foo', $iterator->current());
        unset($args->arguments[0]);
        $this->assertNull($iterator->current());
        $this->assertFalse($iterator->valid());
    }

    public function testNext()
    {
        $iterator = $this->iter();
        $iterator->next();
        $this->assertSame('-f', $iterator->current());
    }

    public function testNextWithDoubleValue()
    {
        $iterator = $this->iter(array('--foo', 'bar', 'baz', '-f'));
        $iterator->next();
        $this->assertSame('-f', $iterator->current());
    }

    public function testNextAtEnd()
    {
        $iterator = $this->iter(array(''));
        $iterator->next();
        $this->assertNull($iterator->current());
    }

    public function testKey()
    {
        $iterator = $this->iter();
        $this->assertSame(0, $iterator->key());
        $iterator->next();
        $this->assertSame(2, $iterator->key());
        $iterator->next();
        $this->assertSame(4, $iterator->key());
    }

    public function provideSingleOptionArgs()
    {
        return array(
            'simple' => array(array('--foo')),
            'value' => array(array('--foo', 'bar')),
            'zero-length' => array(array('--foo', '')),
            'terminator' => array(array('--foo', '--')),
        );
    }

    /**
     * @dataProvider provideSingleOptionArgs
     * @param array $array
     */
    public function testValid(array $array)
    {
        $iterator = $this->iter($array);
        $this->assertTrue($iterator->valid());
        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    public function testRewind()
    {
        $iterator = $this->iter();
        $iterator->next();
        $iterator->rewind();
        $this->assertSame(0, $iterator->key());
    }

    public function provideSingleOptionArguments()
    {
        return array(
            'no' => array(array('--foo'), false),
            'followed' => array(array('--foo', '--bar'), true),
            'capped' => array(array('--foo', '--'), false),
            'value' => array(array('--foo', 'bar'), true),
            'zero-length' => array(array('--foo', ''), true),
        );
    }

    /**
     * @dataProvider provideSingleOptionArguments()
     * @param array $array
     * @param mixed $hasArg
     */
    public function testGetArgument(array $array, $hasArg)
    {
        $iterator = $this->iter($array);
        $this->assertSame($hasArg, $iterator->hasArgument());

        try {
            $actual = $iterator->getArgument();
            $this->assertTrue($hasArg);
            $this->assertSame($array[1], $actual);
        } catch (ArgsException $e) {
            $this->addToAssertionCount(1);
        }
    }

    private function iter(array $array = array('--foo', 'bar', '-f', 'b', '--', 'parameter'))
    {
        $argv = array_merge(
            array('utility'),
            $array
        );

        $args = \Ktomk\Pipelines\Cli\Args::create($argv);

        return new OptionIterator($args);
    }
}
