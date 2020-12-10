<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\Definitions\Service;

/**
 * Class StepContainersTest
 *
 * @package Ktomk\Pipelines\Runner
 * @covers \Ktomk\Pipelines\Runner\Containers
 */
class ContainersTest extends RunnerTestCase
{
    public function testCreation()
    {
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');

        $stepContainers = new Containers($runner, $exec);
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Containers', $stepContainers);
    }

    public function testExecKillAndRemove()
    {
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');

        Containers::execKillAndRemove($exec, null, true, true);
        $this->addToAssertionCount(1);
    }

    public function testExecRun()
    {
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('capture')->willReturn(0);

        $actual = Containers::execRun($exec, array());
        self::assertIsArray($actual);
        self::assertCount(4, $actual);
    }

    public function testExecShutdownContainer()
    {
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $streams = new Streams();
        $flags = new Flags();

        // kill the container
        Containers::execShutdownContainer($exec, $streams, null, 0, $flags, '');
        $this->addToAssertionCount(1);

        // keep the container
        $flags->memory = 0;
        Containers::execShutdownContainer($exec, $streams, null, 0, $flags, '');
        $this->addToAssertionCount(1);

        // keep the container on error
        $flags->memory = $flags::FLAG_KEEP_ON_ERROR;
        Containers::execShutdownContainer($exec, $streams, null, 1, $flags, '');
        $this->addToAssertionCount(1);
    }

    public function testExecRunServiceContainer()
    {
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $service = new Service('super', array('image' => 'klee/red-bridge'));
        $resolver = function ($a) {
            return $a;
        };

        Containers::execRunServiceContainer($exec, $service, $resolver, 'prefix', 'test', array());
        $this->addToAssertionCount(1);
    }

    public function testExecRunServiceContainerAttached()
    {
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $service = new Service('super', array('image' => 'klee/red-bridge'));
        $resolver = function ($a) {
            return $a;
        };

        Containers::execRunServiceContainerAttached($exec, $service, $resolver, 'prefix', 'test', array());
        $this->addToAssertionCount(1);
    }

    public function testExecRunServiceContainerImpl()
    {
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $service = new Service('super', array('image' => 'klee/red-bridge'));
        $resolver = function ($a) {
            return $a;
        };

        $actual = Containers::execRunServiceContainerImpl($exec, $service, $resolver, 'prefix', 'test');
        self::assertNotNull($actual);
        self::assertIsArray($actual(true, '--rm'));
        self::assertIsArray($actual(false, '--detach'));
    }

    public function testCreateStepContainer()
    {
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getExec')->willReturn(
            $this->createMock('Ktomk\Pipelines\Cli\Exec')
        );
        $runner->method('getPrefix')->willReturn('prefix');
        $runner->method('getProject')->willReturn('project');

        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $step->method('getPipeline')->willReturn(
            $this->createMock('Ktomk\Pipelines\File\Pipeline')
        );

        $stepContainers = new Containers($runner);

        $stepContainer = $stepContainers->createStepContainer($step);
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Containers\StepContainer', $stepContainer);
    }
}
