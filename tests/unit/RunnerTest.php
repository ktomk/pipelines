<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\Runner
 */
class RunnerTest extends UnitTestCase
{
    public function testFailOnContainerCreation()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 126);

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');

        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        // TODO set output expectation (now possible thanks to Streams)
        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp',
            $exec,
            null,
            null,
            new Streams()
        );

        $actual = $runner->run($pipeline);
        $this->assertNotSame(0, $actual);
    }

    public function testRunning()
    {
        /** @var MockObject|Exec $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('pass')->willReturn(0);
        $exec->method('capture')->willReturn(0);

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $this->expectOutputRegex('{^\x1d\+\+\+ step #0\n}');
        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp',
            $exec,
            null,
            null,
            new Streams(null, 'php://output')
        );

        $actual = $runner->run($pipeline);
        $this->assertSame(0, $actual);
    }

    public function testErrorStatusWithPipelineHavingEmptySteps()
    {
        /** @var Pipeline|MockObject $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array());

        $exec = new Exec();
        $exec->setActive(false);
        // TODO set output expectation (now possible thanks to Streams)
        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp',
            $exec,
            null,
            null,
            new Streams()
        );
        $status = $runner->run($pipeline);
        $this->assertEquals($runner::STATUS_NO_STEPS, $status);
    }

    public function testHitRecursion()
    {
        $env = $this->createMock('\Ktomk\Pipelines\Runner\Env');
        $env->method('setPipelinesId')->willReturn(true);

        $exec = new Exec();
        $exec->setActive(false);

        // TODO set output expectation (now possible thanks to Streams)
        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp',
            $exec,
            null,
            $env,
            new Streams()
        );
        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $status = $runner->run($pipeline);
        $this->assertSame(127, $status);
    }

    public function testCopy()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 0)
            ->expect('pass', 'docker', 0)
            ->expect('pass', 'docker', 0)
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp',
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(0, $status);
    }

    public function testCopyFails()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 0)
            ->expect('pass', 'docker', 1);

        $this->expectOutputRegex('{Deploy copy failure}');
        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp',
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(1, $status);
    }

    public function testKeepContainerOnError()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 0)
            ->expect('pass', 'docker', 255)
        ;

        $this->expectOutputRegex('{exit status 255, keeping container id \*dry-run\*}');
        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp',
            $exec,
            null, # default flags are important here
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array('fatal me an error'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(255, $status);
    }
}
