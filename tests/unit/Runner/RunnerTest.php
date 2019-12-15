<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\Pipeline;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\Runner\Runner
 */
class RunnerTest extends RunnerTestCase
{
    public function testCreateExCreation()
    {
        $runner = Runner::createEx(
            RunOpts::create('foo'),
            new Directories($_SERVER, $this->getTestProject()),
            $this->createMock('Ktomk\Pipelines\Cli\Exec')
        );
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Runner', $runner);
    }

    public function testErrorStatusWithPipelineHavingEmptySteps()
    {
        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array());

        $exec = new Exec();
        $exec->setActive(false);
        $this->expectOutputRegex('~pipelines: pipeline with no step to execute~');
        $runner = new Runner(
            RunOpts::create('pipelines-unit-test'),
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            new Flags,
            Env::create(),
            new Streams(null, null, 'php://output')
        );
        $status = $runner->run($pipeline);
        $this->assertSame($runner::STATUS_NO_STEPS, $status);
    }

    public function testHitRecursion()
    {
        $env = $this->createMock('\Ktomk\Pipelines\Runner\Env');
        $env->method('setPipelinesId')->willReturn(true);

        $exec = new Exec();
        $exec->setActive(false);

        $this->expectOutputRegex('~^pipelines: .* pipeline inside pipelines recursion detected~');
        $runner = new Runner(
            RunOpts::create('pipelines-unit-test'),
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            new Flags(),
            $env,
            new Streams(null, null, 'php://output')
        );
        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $status = $runner->run($pipeline);
        $this->assertSame(127, $status);
    }

    public function provideRunStatuses()
    {
        return array(
            array(0),
            array(1),
        );
    }

    /**
     * @dataProvider provideRunStatuses
     * @param int $status
     */
    public function testRunPipeline($status)
    {
        /** @var MockObject|Runner $runner */
        $runner = $this->getMockBuilder('Ktomk\Pipelines\Runner\Runner')
            ->setConstructorArgs(array(
                RunOpts::create('foo'),
                $this->createMock('Ktomk\Pipelines\Runner\Directories'),
                ExecTester::create($this),
                new Flags(),
                Env::create(),
                Streams::create()
            ))
            ->setMethods(array('runStep'))
            ->getMock();
        $runner->method('runStep')->willReturn($status);

        $step = $this->createTestStep();
        $this->assertSame($status, $runner->run($step->getPipeline()));
    }

    public function testRunStep()
    {
        $exec = new Exec();
        $exec->setActive(false);

        /** @var MockObject|Runner $runner */
        $runner = $this->getMockBuilder('Ktomk\Pipelines\Runner\Runner')
            ->setConstructorArgs(array(
                RunOpts::create('foo'),
                $this->createMock('Ktomk\Pipelines\Runner\Directories'),
                $exec,
                new Flags(),
                Env::create(),
                new Streams()
            ))
            ->setMethods(null)
            ->getMock();

        $step = $this->createTestStep();
        $this->assertSame(0, $runner->runStep($step));
    }
}
