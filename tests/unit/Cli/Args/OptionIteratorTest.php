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

        self::assertInstanceOf('Ktomk\Pipelines\Cli\Args\OptionIterator', $iterator);
    }

    public function testCurrent()
    {
        $iterator = $this->iter();
        self::assertSame('--foo', $iterator->current());
        $iterator->rewind();
        self::assertSame('--foo', $iterator->current());
    }

    public function testInvalidateCurrent()
    {
        $args = new ArgsTester();
        $args->arguments = array('--foo', 'bar', '-f', 'b', '--', 'parameter');
        $iterator = new OptionIterator($args);
        self::assertSame('--foo', $iterator->current());
        unset($args->arguments[0]);
        self::assertFalse($iterator->valid());
        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage('Invalid iterator state for current()');
        $iterator->current();
    }

    public function testNext()
    {
        $iterator = $this->iter();
        $iterator->next();
        self::assertSame('-f', $iterator->current());
    }

    public function testNextWithDoubleValue()
    {
        $iterator = $this->iter(array('--foo', 'bar', 'baz', '-f'));
        $iterator->next();
        self::assertSame('-f', $iterator->current());
    }

    public function testNextAtEnd()
    {
        $iterator = $this->iter(array(''));
        $iterator->next();
        self::assertFalse($iterator->valid());
    }

    public function testKey()
    {
        $iterator = $this->iter();
        self::assertSame(0, $iterator->key());
        $iterator->next();
        self::assertSame(2, $iterator->key());
        $iterator->next();
        self::assertSame(4, $iterator->key());
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
     *
     * @param array $array
     */
    public function testValid(array $array)
    {
        $iterator = $this->iter($array);
        self::assertTrue($iterator->valid());
        $iterator->next();
        self::assertFalse($iterator->valid());
    }

    public function testRewind()
    {
        $iterator = $this->iter();
        $iterator->next();
        $iterator->rewind();
        self::assertSame(0, $iterator->key());
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
     *
     * @param array $array
     * @param mixed $hasArg
     */
    public function testGetArgument(array $array, $hasArg)
    {
        $iterator = $this->iter($array);
        self::assertSame($hasArg, $iterator->hasArgument());

        try {
            $actual = $iterator->getArgument();
            self::assertTrue($hasArg);
            self::assertSame($array[1], $actual);
        } catch (ArgsException $e) {
            $this->addToAssertionCount(1);
        }
    }

    public function testGetEqualArgument()
    {
        $iterator = $this->iter();

        $iterator->seekOption('foo');
        self::assertSame(0, $iterator->key());
        self::assertSame('--foo', $iterator->current());

        $iterator->rewind();
        $iterator->seekOption(array('foo-bar', 'foo'));
        self::assertSame(0, $iterator->key());
        self::assertSame('--foo', $iterator->current());

        $iterator->seekOption('user');
        self::assertSame(4, $iterator->key());
        self::assertSame('--user=1000:1000', $iterator->current());
    }

    private function iter(array $array = array('--foo', 'bar', '-f', 'b', '--user=1000:1000', '--', 'parameter'))
    {
        $argv = array_merge(
            array('utility'),
            $array
        );

        $args = \Ktomk\Pipelines\Cli\Args::create($argv);

        return new OptionIterator($args);
    }
}
