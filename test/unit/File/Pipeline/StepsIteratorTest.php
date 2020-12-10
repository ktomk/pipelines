<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\File\Pipeline\StepsIterator
 */
class StepsIteratorTest extends TestCase
{
    public function testCreation()
    {
        $iter = new StepsIterator(new \EmptyIterator());
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepsIterator', $iter);
    }

    public function testEmptyIteration()
    {
        $iter = new StepsIterator(new \EmptyIterator());

        $iter->rewind();
        $this->addToAssertionCount(1);

        try {
            $iter->key();
            self::fail('An expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }

        try {
            $iter->current();
            self::fail('An expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }

        self::assertFalse($iter->valid());

        $iter->next();
        $this->addToAssertionCount(1);

        try {
            self::assertFalse($iter->valid());
            self::fail('An expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }
    }

    public function testStepsIteration()
    {
        $array = array(
            $this->createMock('Ktomk\Pipelines\File\Pipeline\Step'),
            /* trigger: manual */ $this->createConfiguredMock(
                'Ktomk\Pipelines\File\Pipeline\Step',
                array('isManual' => true, 'getIndex' => 1)
            ),
        );

        $iter = new StepsIterator(new \ArrayIterator($array));

        self::assertTrue($iter->valid(), 'ArrayIterator expected valid on creation');
        $iter->rewind();
        $this->addToAssertionCount(1);
        self::assertTrue($iter->valid(), 'ArrayIterator expected valid when rewound');
        self::assertSame(0, $iter->key());
        self::assertSame($array[0], $iter->current());
        self::assertFalse($iter->isManual());
        self::assertSame(0, $iter->getIndex());
        $iter->next();
        self::assertSame(1, $iter->getIndex());
        self::assertTrue($iter->isManual());
        self::assertSame(1, $iter->getStepIndex());
        self::assertFalse($iter->valid());

        $iter->rewind();
        $iter->setNoManual(true);
        $iter->next();
        self::assertTrue($iter->valid(), 'no manual still valid on manual step');
        self::assertFalse($iter->isManual());
    }
}
