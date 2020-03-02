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
        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Steps', $steps);

        return $steps;
    }

    public function testParseErrors()
    {
        $pipeline = $this->getTestPipeline();

        # things are needed
        try {
            new Steps($pipeline, array());
            $this->fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            $this->addToAssertionCount(1);
        }

        # steps (the list) is needed
        try {
            new Steps($pipeline, array('foo'));
            $this->fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            $this->addToAssertionCount(1);
        }

        # concrete steps are needed
        try {
            new Steps($pipeline, array(array()));
            $this->fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            $this->addToAssertionCount(1);
        }

        # concrete steps are needed
        try {
            new Steps($pipeline, array(array('wrong-name' => array())));
            $this->fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            $this->addToAssertionCount(1);
        }
        # concrete steps are needed
        try {
            new Steps($pipeline, array(array('parallel' => array(array()))));
            $this->fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            $this->addToAssertionCount(1);
        }
    }

    public function testGetSteps()
    {
        $pipeline = $this->getTestPipeline();
        $definition = array(array('step' => array('script' => array(':'))));
        $steps = new Steps($pipeline, $definition);
        $array = $steps->getSteps();
        self::assertIsArray($array);
        self::assertCount(1, $array);
        $this->assertArrayHasKey(0, $array);
        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Step', $array[0]);
    }

    /**
     * @covers \Ktomk\Pipelines\File\Pipeline\Steps::setGetIteratorFunctor
     */
    public function testSetGetIteratorFunctor()
    {
        $pipeline = $this->getTestPipeline();
        $definition = array(array('step' => array('script' => array(':'))));
        $steps = new Steps($pipeline, $definition);
        $this->assertCount(count($steps), $steps->getIterator(), 'pre-condition');
        $steps->setGetIteratorFunctor(function (Steps $steps) {
            return new \ArrayIterator(array_merge($steps->getSteps(), $steps->getSteps()));
        });
        $this->assertCount(count($steps) * 2, $steps->getIterator());
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

        $this->assertArrayHasKey(0, $array);
        $this->assertArrayHasKey(1, $array);
        $this->assertArrayHasKey(2, $array);
        $this->assertArrayNotHasKey(-1, $array);
        $this->assertArrayNotHasKey(3, $array);

        $this->assertContainsOnlyInstancesOf('Ktomk\Pipelines\File\Pipeline\Step', $array);
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

        $this->assertArrayHasKey(0, $steps);
        $this->assertArrayHasKey(1, $steps);
        $this->assertArrayHasKey(2, $steps);
        $this->assertArrayNotHasKey(-1, $steps);
        $this->assertArrayNotHasKey(3, $steps);

        $this->assertContainsOnlyInstancesOf('Ktomk\Pipelines\File\Pipeline\Step', $steps);

        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Step', $steps[0]);

        $this->assertTrue($steps->offsetExists(0));
        $this->assertTrue($steps->offsetExists(1));
        $this->assertTrue($steps->offsetExists(2));
        $this->assertFalse($steps->offsetExists(-1));
        $this->assertFalse($steps->offsetExists(3));

        try {
            $steps[0] = 'foo';
            $this->fail('an expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }

        try {
            unset($steps[0]);
            $this->fail('an expected exception has not been thrown');
        } catch (\BadMethodCallException $ex) {
            $this->addToAssertionCount(1);
        }
    }

    public function testGetPipeline()
    {
        $definition = array(array('step' => array('script' => array(':'))));
        $pipeline = $this->getTestPipeline();
        $steps = new Steps($pipeline, $definition);

        $this->assertSame($pipeline, $steps->getPipeline());
    }

    /**
     * @depends testCreation
     *
     * @param Steps $steps
     */
    public function testJsonSerialize(Steps $steps)
    {
        $this->assertArrayHasKey(
            'steps',
            $steps->jsonSerialize()
        );
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
