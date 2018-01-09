<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\File\ParseException;
use PHPUnit\Framework\TestCase;

/**
 * Class PipelineTest
 *
 * @covers \Ktomk\Pipelines\Pipeline
 */
class PipelineTest extends TestCase
{

    public function testCreation()
    {
        $file = new File(array('pipelines' => array('default' => array())));
        $definition = array(array('step' => array('script' => array(':'))));
        $pipeline = new Pipeline($file, $definition);
        $this->assertInstanceOf('Ktomk\Pipelines\Pipeline', $pipeline);
    }

    public function testParseErrors()
    {
        $file = new File(array('pipelines' => array('default' => array())));

        # things are needed
        try {
            new Pipeline($file, array());
            $this->fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            $this->addToAssertionCount(1);
        }

        # steps (the list) is needed
        try {
            new Pipeline($file, array('foo'));
            $this->fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            $this->addToAssertionCount(1);
        }

        # concrete steps are needed
        try {
            new Pipeline($file, array(array()));
            $this->fail('an expected exception has not been thrown');
        } catch (ParseException $e) {
            $this->addToAssertionCount(1);
        }
    }

    public function testGetSteps()
    {
        $file = new File(array('pipelines' => array('default' => array())));
        $definition = array(array('step' => array('script' => array(':'))));
        $pipeline = new Pipeline($file, $definition);
        $steps = $pipeline->getSteps();
        $this->assertArrayHasKey(0, $steps);
        $this->assertInstanceOf('Ktomk\Pipelines\Step', $steps[0]);
    }

    public function testGetFile()
    {
        $file = new File(array('pipelines' => array('default' => array())));
        $definition = array(array('step' => array('script' => array(':'))));
        $pipeline = new Pipeline($file, $definition);
        $this->assertSame($file, $pipeline->getFile());
    }

    public function testGetPipelineId()
    {
        $file = new File(array('pipelines' => array('default' => array(
            array('step' => array('script' => array(':')))
        ))));
        $pipeline = $file->getById('default');
        $actual = $pipeline->getId();
        $this->assertSame('default', $actual);
    }
}
