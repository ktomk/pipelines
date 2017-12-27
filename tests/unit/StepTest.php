<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\Step
 */
class StepTest extends UnitTestCase
{
    public function testCreation()
    {
        $step = $this->createStep();
        $this->assertInstanceOf('Ktomk\Pipelines\Step', $step);
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'script' requires a list of commands
     */
    public function testRequiresListOfCommands()
    {
        $this->createStep(array(
            'script' => array(),
        ));
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'step' requires a script
     */
    public function testRequiresScript()
    {
        $this->createStep(array());
    }


    public function testGetImage()
    {
        $step = $this->createStep(array(
            'image' => 'expected',
            'script' => array(":"),
        ));
        $this->assertSame('expected', $step->getImage());
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
            'script' => array(":"),
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
        $this->assertSame(array(':'),$step->getScript());
    }

    /**
     * @param array|null $array [optional]
     * @return Step
     */
    private function createStep(array $array = null)
    {
        if ($array === null) {
            # a (minimum) array to successfully create a step
            $array = array(
                'script' => array(":"),
            );
        }

        /** @var File|MockObject $pipeline */
        $file = $this->createMock('Ktomk\Pipelines\File');

        /** @var Pipeline|MockObject $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getFile')->willReturn($file);

        $step = new Step($pipeline, $array);

        return $step;
    }
}
