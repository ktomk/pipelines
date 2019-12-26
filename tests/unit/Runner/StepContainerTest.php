<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\ExecTest;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\File\Step;
use Ktomk\Pipelines\TestCase;

/**
 * Class StepContainerTest
 *
 * @package Ktomk\Pipelines\Runner
 * @covers \Ktomk\Pipelines\Runner\StepContainer
 */
class StepContainerTest extends TestCase
{
    public function testCreation()
    {
        $step = $this->getStepMock();

        $container = new StepContainer($step, new ExecTester($this));
        $this->assertNotNull($container);

        $container = StepContainer::create($step);
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\StepContainer', $container);

        return $container;
    }
    public function testCreateName()
    {
        $expected = 'pipelines-1.no-name.null.test-project';
        $actual = StepContainer::createName(
            $this->getStepMock(),
            'pipelines',
            'test-project'
        );
        $this->assertSame($expected, $actual);
    }

    public function testGenerateName()
    {
        $container = new StepContainer($this->getStepMock(), new ExecTester($this));
        $expected = 'pipelines-1.no-name.null.test-project';
        $actual = $container->generateName('pipelines', 'test-project');
        $this->assertSame($expected, $actual);
    }

    public function testGetName()
    {
        $container = new StepContainer($this->getStepMock(), new ExecTester($this));
        $this->assertNull($container->getName(), 'no name generated yet');
        $container->generateName('pipelines', 'test-project');
        $expected = 'pipelines-1.no-name.null.test-project';
        $actual = $container->getName();
        $this->assertSame($expected, $actual);
    }

    public function testKeepOrKillThrowsException()
    {
        $container = new StepContainer($this->getStepMock(), new ExecTester($this));

        $this->setExpectedException('BadMethodCallException', 'Container has no name yet');
        $container->keepOrKill(false);
    }

    public function testKeepOrKill()
    {
        $exec = new ExecTester($this);
        $container = new StepContainer($this->getStepMock(), $exec);
        $container->generateName('pipelines', 'test-project');

        $exec->expect('capture', 'docker');
        $this->assertNull($container->keepOrKill(false));

        $exec->expect('capture', 'docker', '1234567');
        $this->assertSame('1234567', $container->keepOrKill(true));

        $this->assertSame('1234567', $container->getId());
    }

    /**
     * @return \Ktomk\Pipelines\File\Step|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getStepMock()
    {
        $step = $this->createPartialMock(
            'Ktomk\Pipelines\File\Step',
            array('getPipeline')
        );
        $step->method('getPipeline')
            ->willReturn(
                $this->createMock('Ktomk\Pipelines\File\Pipeline')
            );

        return $step;
    }
}
