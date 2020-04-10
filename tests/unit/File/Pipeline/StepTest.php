<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\File\Pipeline\Step
 */
class StepTest extends TestCase
{
    public function testCreation()
    {
        $step = $this->createStep();
        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Step', $step);
    }

    public function testGetArtifacts()
    {
        $array = array(
            'script' => array(':'),
            'artifacts' => array('build/html/testdox.html'),
        );
        $step = $this->createStep($array);
        $actual = $step->getArtifacts();
        $this->assertInstanceOf(
            'Ktomk\Pipelines\File\Artifacts',
            $actual
        );
    }

    public function testGetArtifactsWithNoArtifactsNode()
    {
        $step = $this->createStep();
        $this->assertNull($step->getArtifacts());
    }

    /**
     */
    public function testRequiresListOfCommands()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'script\' requires a list of commands');

        $this->createStep(array(
            'script' => array(),
        ));
    }

    /**
     */
    public function testRequiresScript()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'step\' requires a script');

        $this->createStep(array());
    }

    /**
     */
    public function testRequiresScriptAsListOfCommands()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'script\' requires a list of commands, step #1 is not a command');

        $this->createStep(array(
            'script' => array(
                ': # valid',
                array('' => 'step #1 is broken'),
            ),
        ));
    }

    public function testGetImage()
    {
        $step = $this->createStep(array(
            'image' => 'expected',
            'script' => array(':'),
        ));
        $this->assertSame('expected', (string)$step->getImage());
    }

    /**
     */
    public function testInvalidImageName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('invalid Docker image name');

        $this->createStep(array(
            'image' => 'php:5.6find . -name .libs -a -type d|xargs rm -rf',
            'script' => array(':'),
        ));
    }

    public function testGetImageFallback()
    {
        $step = $this->createStep();
        $this->assertNull($step->getImage());
    }

    public function testGetName()
    {
        $step = $this->createStep(array(
            'name' => 'expected',
            'script' => array(':'),
        ));
        $this->assertSame('expected', $step->getName());
    }

    public function testGetNameFallback()
    {
        $step = $this->createStep();
        $this->assertNull($step->getName());
    }

    public function testGetScript()
    {
        $step = $this->createStep();
        $this->assertSame(array(':'), $step->getScript());
    }

    public function testJsonSerialize()
    {
        $actual = $this->createStep()->jsonSerialize();
        $this->assertArrayHasKey('image', $actual);
    }

    public function testGetEnv()
    {
        $this->assertSame(array(), $this->createStep()->getEnv());
    }

    public function testGetIndex()
    {
        $index = $this->createStep()->getIndex();
        $this->assertIsInt($index);
        $this->assertGreaterThanOrEqual(0, $index);
    }

    public function testGetServices()
    {
        $step = $this->createStep();

        $this->assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepServices', $step->getServices());
    }

    public function testManual()
    {
        $manualStep = array(
            'trigger' => 'manual',
            'script' => array(':'),
        );

        $this->assertFalse($this->createStep(null, 0)->isManual());

        $this->assertFalse($this->createStep($manualStep, 0)->isManual(), 'first step can never be manual');

        $this->assertFalse($this->createStep(null, 1)->isManual());

        $this->assertTrue($this->createStep($manualStep, 1)->isManual(), 'second step can be manual');
    }

    public function testGetAfterScript()
    {
        $afterScriptStep = array(
            'script' => array(':'),
            'after-script' => array(':'),
        );
        $this->assertSame(array(), $this->createStep(null)->getAfterScript());
        $this->assertSame(array(':'), $this->createStep($afterScriptStep)->getAfterScript());
    }

    /**
     * @param null|array $array [optional]
     *
     * @param int $index [optional]
     *
     * @return Step
     */
    private function createStep(array $array = null, $index = 0)
    {
        if (null === $array) {
            # a (minimum) array to successfully create a step
            $array = array(
                'script' => array(':'),
            );
        }

        /** @var File|MockObject $pipeline */
        $file = $this->createMock('Ktomk\Pipelines\File\File');

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getFile')->willReturn($file);

        return new Step($pipeline, $index, $array);
    }
}
