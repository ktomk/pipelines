<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\ParseException;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\TestCase;

/**
 * Class PipelineTest
 *
 * @covers \Ktomk\Pipelines\File\Pipeline\Steps
 */
class StepsTest extends TestCase
{
    /**
     * @return Steps
     */
    public function testCreation()
    {
        $definition = array(array('step' => array('script' => array(':'))));
        $pipeline = $this->getTestPipeline();
        $steps = new Steps($pipeline, $definition);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Steps', $steps);

        return $steps;
    }

    /**
     * @covers \Ktomk\Pipelines\File\Pipeline\StepParser
     */
    public function testParseErrors()
    {
        $pipeline = $this->getTestPipeline();

        # things are needed
        $this->assertParseException(
            $pipeline,
            array(),
            'Steps requires a tree of steps'
        );

        # steps (the list) is needed
        $this->assertParseException(
            $pipeline,
            array('foo'),
            'Step expected, got string'
        );

        # concrete steps are needed
        $this->assertParseException(
            $pipeline,
            array(array()),
            'Step expected, got empty array'
        );

        # concrete steps are needed
        $this->assertParseException(
            $pipeline,
            array(array('wrong-name' => array())),
            "Unexpected pipeline property 'wrong-name', expected 'step' or 'parallel'"
        );

        # concrete steps are needed
        $this->assertParseException(
            $pipeline,
            array(array('parallel' => array(array()))),
            'Parallel step must consist of steps only'
        );

        # trigger: manual not on first step
        $this->assertParseException(
            $pipeline,
            array(array('step' => array('trigger' => 'manual'))),
            "The first step of a pipeline can't be manually triggered"
        );

        # trigger not in parallel step
        $this->assertParseException(
            $pipeline,
            array(
                array('step' => array('trigger' => 'automatic', 'script' => array(':'))),
                array('parallel' => array(array('step' => array('trigger' => 'manual')))),
            ),
            "Unexpected property 'trigger' in parallel step"
        );

        # trigger: manual or automatic only
        $this->assertParseException(
            $pipeline,
            array(array('step' => array('trigger' => 'foo'))),
            "'trigger' expects either 'manual' or 'automatic'"
        );
    }

    public function testGetSteps()
    {
        $pipeline = $this->getTestPipeline();
        $definition = array(array('step' => array('script' => array(':'))));
        $steps = new Steps($pipeline, $definition);
        $array = $steps->getSteps();
        self::assertIsArray($array);
        self::assertCount(1, $array);
        self::assertArrayHasKey(0, $array);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Step', $array[0]);
    }

    /**
     * @covers \Ktomk\Pipelines\File\Pipeline\Steps::setGetIteratorFunctor
     */
    public function testSetGetIteratorFunctor()
    {
        $pipeline = $this->getTestPipeline();
        $definition = array(array('step' => array('script' => array(':'))));
        $steps = new Steps($pipeline, $definition);
        self::assertCount(count($steps), $steps->getIterator(), 'pre-condition');
        $steps->setGetIteratorFunctor(function (Steps $steps) {
            return new \ArrayIterator(array_merge($steps->getSteps(), $steps->getSteps()));
        });
        self::assertCount(count($steps) * 2, $steps->getIterator());
    }

    public function testGetStepsInherited()
    {
        $pipeline = $this->getTestPipeline();
        $definition = array(
            array('step' => array('script' => array(': 0'))),
            array('parallel' => array(
                array('step' => array('script' => array(': 1'))),
                array('step' => array('script' => array(': 2'))),
            )),
        );
        $steps = new Steps($pipeline, $definition);
        $array = $steps->getSteps();
        self::assertIsArray($array);
        self::assertCount(3, $array);

        self::assertArrayHasKey(0, $array);
        self::assertArrayHasKey(1, $array);
        self::assertArrayHasKey(2, $array);
        self::assertArrayNotHasKey(-1, $array);
        self::assertArrayNotHasKey(3, $array);

        self::assertContainsOnlyInstancesOf('Ktomk\Pipelines\File\Pipeline\Step', $array);
    }

    public function testGetStepsArrayInherited()
    {
        $pipeline = $this->getTestPipeline();
        $definition = array(
            array('step' => array('script' => array(': 0'))),
            array('parallel' => array(
                array('step' => array('script' => array(': 1'))),
                array('step' => array('script' => array(': 2'))),
            )),
        );
        $steps = new Steps($pipeline, $definition);
        self::assertCount(3, $steps);

        self::assertArrayHasKey(0, $steps);
        self::assertArrayHasKey(1, $steps);
        self::assertArrayHasKey(2, $steps);
        self::assertArrayNotHasKey(-1, $steps);
        self::assertArrayNotHasKey(3, $steps);

        self::assertContainsOnlyInstancesOf('Ktomk\Pipelines\File\Pipeline\Step', $steps);

        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Step', $steps[0]);

        self::assertTrue($steps->offsetExists(0));
        self::assertTrue($steps->offsetExists(1));
        self::assertTrue($steps->offsetExists(2));
        self::assertFalse($steps->offsetExists(-1));
        self::assertFalse($steps->offsetExists(3));

        try {
            $steps[0] = 'foo';
            self::fail('an expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }

        try {
            unset($steps[0]);
            self::fail('an expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }
    }

    public function testGetPipeline()
    {
        $definition = array(array('step' => array('script' => array(':'))));
        $pipeline = $this->getTestPipeline();
        $steps = new Steps($pipeline, $definition);

        self::assertSame($pipeline, $steps->getPipeline());
    }

    /**
     * @depends testCreation
     *
     * @param Steps $steps
     */
    public function testJsonSerialize(Steps $steps)
    {
        self::assertArrayHasKey(
            'steps',
            $steps->jsonSerialize()
        );
    }

    public function testFullIter()
    {
        self::assertNotNull(Steps::fullIter(null));

        $steps = $this->createConfiguredMock('Ktomk\Pipelines\File\Pipeline\Steps', array(
            'getIterator' => $this->createMock('Ktomk\Pipelines\File\Pipeline\StepsIterator'),
        ));

        self::assertNotNull(Steps::fullIter($steps));
    }

    public function testTestParseEmptyStep()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/invalid/pipeline-step.yml');

        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('file parse error: step requires a script');
        $file->getDefault();
    }

    private function assertParseException(Pipeline $pipeline, array $array, $expected)
    {
        try {
            new Steps($pipeline, $array);
            self::fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            self::assertSame($expected, $e->getParseMessage());
        }
    }

    /**
     * @return Pipeline
     */
    private function getTestPipeline()
    {
        $file = new File(array('pipelines' => array('default' => array())));
        $definition = array(array('step' => array('script' => array(':'))));

        return new Pipeline($file, $definition);
    }
}
