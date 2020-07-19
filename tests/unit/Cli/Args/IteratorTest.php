<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use Ktomk\Pipelines\TestCase;

/**
 * Class IteratorTest
 *
 * @covers \Ktomk\Pipelines\Cli\Args\Iterator
 */
class IteratorTest extends TestCase
{
    public function testCreation()
    {
        $args = new ArgsTester();
        $args->arguments = array('--foo', 'bar', '', '--', 'parameter');
        $iterator = new Iterator($args);
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Args\Iterator', $iterator);
    }

    public function testIteration()
    {
        $args = new ArgsTester();
        $args->arguments = array('--foo', 'bar', '', '--', 'parameter');
        $iterator = new Iterator($args);
        $actual = iterator_to_array($iterator, true);
        self::assertSame($args->arguments, $actual);
    }

    public function testInvalidation()
    {
        $args = new ArgsTester();
        $args->arguments = array('--foo', 'bar', '', '--', 'parameter');
        $iterator = new Iterator($args);
        self::assertSame('--foo', $iterator->current());
        unset($args->arguments[0]);
        self::assertNull($iterator->current());
        self::assertFalse($iterator->valid());
    }

    public function testHasNext()
    {
        $args = new ArgsTester();
        $args->arguments = array('--foo', 'bar');
        $iterator = new Iterator($args);
        self::assertNotNull($iterator->getNext());
        $iterator->next();
        self::assertNull($iterator->getNext());
    }

    public function testNextWhileInvalidated()
    {
        $args = new ArgsTester();
        $args->arguments = array();
        $iterator = new Iterator($args);
        self::assertFalse($iterator->valid());
        $iterator->next();
        self::assertFalse($iterator->valid());
    }
}
