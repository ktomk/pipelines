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
            '/tmp/pipelines-test-suite',
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
            '/tmp/pipelines-test-suite',
            $exec,
            null,
            null,
            new Streams(null, 'php://output')
        );

        $actual = $runner->run($pipeline);
        $this->assertSame(0, $actual);
    }

    public static function setUpBeforeClass()
    {
        // this test-case operates on a (clean) temporary directory
        if (is_dir('/tmp/pipelines-test-suite')) {
            shell_exec('rm -rf "/tmp/pipelines-test-suite/"');
            mkdir('/tmp/pipelines-test-suite');
        }
        parent::setUpBeforeClass();
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
            '/tmp/pipelines-test-suite',
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
            '/tmp/pipelines-test-suite',
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
            '/tmp/pipelines-test-suite',
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams()
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
            '/tmp/pipelines-test-suite',
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
            '/tmp/pipelines-test-suite',
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

    public function testArtifacts()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 0)
            ->expect('pass', 'docker', 0)
            ->expect('pass', 'docker', 0)
            ->expect('capture', 'docker',"./build/foo-package.tgz")
            ->expect('pass', 'docker exec -w /app \'*dry-run*\' tar c -f - build/foo-package.tgz | tar x -f - -C /tmp/pipelines-test-suite', 0)
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp/pipelines-test-suite',
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
                'artifacts' => array('build/foo-package.tgz'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(0, $status);
    }

    public function testArtifactsNoMatch()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 0)
            ->expect('pass', 'docker', 0)
            ->expect('pass', 'docker', 0)
            ->expect('capture', 'docker',"./build/foo-package.tgz")
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp/pipelines-test-suite',
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
                'artifacts' => array('build/bar-package.tgz'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(0, $status);
    }

    public function testArtifactsFailure()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 0)
            ->expect('pass', 'docker', 0)
            ->expect('pass', 'docker', 0)
            ->expect('capture', 'docker',"./build/foo-package.tgz")
            ->expect('pass', 'docker exec -w /app \'*dry-run*\' tar c -f - build/foo-package.tgz | tar x -f - -C /tmp/pipelines-test-suite', 1)
        ;

        $this->expectOutputString("Artifact failure: 'build/foo-package.tgz'\n");
        $runner = new Runner(
            'pipelines-unit-test',
            '/tmp/pipelines-test-suite',
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
                'artifacts' => array('build/foo-package.tgz'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(0, $status);
    }
}
