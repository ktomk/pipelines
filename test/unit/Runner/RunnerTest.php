<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\Yaml\Yaml;
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
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Runner', $runner);
    }

    public function testErrorStatusWithPipelineHavingEmptySteps()
    {
        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(
            $this->createPartialMock('Ktomk\Pipelines\File\Pipeline\Steps', array())
        );

        $exec = new Exec();
        $exec->setActive(false);
        $this->expectOutputRegex('~pipelines: pipeline with no step to execute~');
        $runner = new Runner(
            RunOpts::create('pipelinesunittest'),
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            new Flags(),
            Env::createEx(),
            new Streams(null, null, 'php://output')
        );
        $status = $runner->run($pipeline);
        self::assertSame($runner::STATUS_NO_STEPS, $status);
    }

    public function testHitRecursion()
    {
        $env = $this->createMock('\Ktomk\Pipelines\Runner\Env');
        $env->method('setPipelinesId')->willReturn(true);

        $exec = new Exec();
        $exec->setActive(false);

        $this->expectOutputRegex('~^pipelines: .* pipeline inside pipelines recursion detected~');
        $runner = new Runner(
            RunOpts::create('pipelinesunittest'),
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            new Flags(),
            $env,
            new Streams(null, null, 'php://output')
        );
        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $status = $runner->run($pipeline);
        self::assertSame(127, $status);
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
     *
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
                Env::createEx(),
                Streams::create(),
            ))
            ->setMethods(array('runStep'))
            ->getMock();
        $runner->method('runStep')->willReturn($status);

        $step = $this->createTestStep();
        self::assertSame($status, $runner->run($step->getPipeline()));
    }

    public function testRunStep()
    {
        $exec = new Exec();
        $exec->setActive(false);

        /** @var MockObject|Runner $runner */
        $directories = $this->createMock('Ktomk\Pipelines\Runner\Directories');
        $directories->method('getProjectDirectory')->willReturn('/my-app');
        $runner = $this->getMockBuilder('Ktomk\Pipelines\Runner\Runner')
            ->setConstructorArgs(array(
                RunOpts::create('foo'),
                $directories,
                $exec,
                new Flags(),
                Env::createEx(),
                new Streams(),
            ))
            ->setMethods(null)
            ->getMock();

        $step = $this->createTestStep();
        self::assertSame(0, $runner->runStep($step));
    }

    public function testStopAtManualStep()
    {
        $exec = new Exec();
        $exec->setActive(false);

        $directories = $this->createMock('Ktomk\Pipelines\Runner\Directories');
        $directories->method('getProjectDirectory')->willReturn('/my-app');

        /** @var MockObject|Runner $runner */
        $runner = $this->getMockBuilder('Ktomk\Pipelines\Runner\Runner')
            ->setConstructorArgs(array(
                RunOpts::create('foo'),
                $directories,
                $exec,
                new Flags(),
                Env::createEx(),
                new Streams(null, 'php://output', 'php://output'),
            ))
            ->setMethods(null)
            ->getMock();

        $path = __DIR__ . '/../../data/yml/steps.yml';
        $array = Yaml::file($path);
        $file = new File($array);
        $pipeline = $file->getDefault();

        $this->expectOutputRegex(
            '~^pipelines: step #4 is manual. use `--steps 4-` to continue or `--no-manual` to override$~m'
        );
        self::assertSame(0, $runner->run($pipeline));
    }

    public function testGetProjectDirectory()
    {
        $runner = $this->createPartialMock('Ktomk\Pipelines\Runner\Runner', array(
            'getDirectories',
        ));
        $runner->method('getDirectories')->willReturn(
            $this->createConfiguredMock('Ktomk\Pipelines\Runner\Directories', array(
                'getProjectDirectory' => '/host/path/to/project',
            ))
        );

        self::assertSame('/host/path/to/project', $runner->getProjectDirectory());
    }
}
