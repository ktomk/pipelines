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
        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepsIterator', $iter);
    }

    public function testEmptyIteration()
    {
        $iter = new StepsIterator(new \EmptyIterator());

        $iter->rewind();
        $this->addToAssertionCount(1);

        try {
            $iter->key();
            $this->fail('An expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }

        try {
            $iter->current();
            $this->fail('An expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }

        $this->assertFalse($iter->valid());

        $iter->next();
        $this->addToAssertionCount(1);

        try {
            $this->assertFalse($iter->valid());
            $this->fail('An expected exception has not been thrown');
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

        $this->assertTrue($iter->valid(), 'ArrayIterator expected valid on creation');
        $iter->rewind();
        $this->addToAssertionCount(1);
        $this->assertTrue($iter->valid(), 'ArrayIterator expected valid when rewound');
        $this->assertSame(0, $iter->key());
        $this->assertSame($array[0], $iter->current());
        $this->assertFalse($iter->isManual());
        $this->assertSame(0, $iter->getIndex());
        $iter->next();
        $this->assertSame(1, $iter->getIndex());
        $this->assertTrue($iter->isManual());
        $this->assertSame(1, $iter->getStepIndex());
        $this->assertFalse($iter->valid());

        $iter->rewind();
        $iter->setNoManual(true);
        $iter->next();
        $this->assertTrue($iter->valid(), 'no manual still valid on manual step');
        $this->assertFalse($iter->isManual());
    }
}
