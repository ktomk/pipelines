<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\DestructibleString;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\File\Step;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\Runner\Runner
 */
class RunnerTest extends TestCase
{
    /**
     * @var string fixture of command for deploy mode copy
     * @see setUp for initialization
     */
    private $deploy_copy_cmd;
    private $deploy_copy_cmd_2;

    /**
     * @var array
     */
    private $cleaners = array();

    protected function setUp()
    {
        parent::setUp();

        $this->deploy_copy_cmd = '~cd ' . sys_get_temp_dir() . '/pipelines-cp\.[^/]+/\. ' .
            "&& echo 'app' | tar c -h -f - --no-recursion app " .
            "| docker  cp - '\\*dry-run\\*:/\\.'~";

        $this->deploy_copy_cmd_2 = '~cd ' . sys_get_temp_dir() . '/pipelines-test-suite[^/]*/\. ' .
            '&& tar c -f - . ' .
            "| docker  cp - '\\*dry-run\\*:/app'~";
    }

    public function testFailOnContainerCreation()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 1);
        $exec->expect('capture', 'docker', 126);

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');

        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $this->expectOutputRegex('~pipelines: setting up the container failed~');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            null,
            null,
            new Streams(null, null, 'php://output')
        );

        $actual = $runner->run($pipeline);
        $this->assertNotSame(0, $actual);
    }

    public function testRunning()
    {
        /** @var Exec|MockObject $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('pass')->willReturn(0);
        $exec->method('capture')->willReturn(0);

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $this->expectOutputRegex('{^\x1d\+\+\+ step #1\n}');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
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
        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array());

        $exec = new Exec();
        $exec->setActive(false);
        $this->expectOutputRegex('~pipelines: pipeline with no step to execute~');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            null,
            null,
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
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            null,
            $env,
            new Streams(null, null, 'php://output')
        );
        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $status = $runner->run($pipeline);
        $this->assertSame(127, $status);
    }

    public function testCopy()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1) # zap
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', 0) # docker kill
            ->expect('capture', 'docker', 0) # docker rm
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            Runner::FLAG_DEPLOY_COPY | Runner::FLAGS,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
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
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 1);

        $this->expectOutputRegex('{^pipelines: deploy copy failure}');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            Runner::FLAG_DEPLOY_COPY | Runner::FLAGS,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(1, $status);
    }

    public function testCopyFailsAtSecondStage()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 1);

        $this->expectOutputRegex('{^pipelines: deploy copy failure}');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            Runner::FLAG_DEPLOY_COPY | Runner::FLAGS,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(1, $status);
    }

    public function testKeepContainerOnErrorWithNonExistentContainer()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1) # no id for name of potential re-use
            ->expect('capture', 'docker', 0) # run the container
            ->expect('pass', '~ docker exec ~', 255)
        ;

        $this->keepContainerOnErrorExecTest($exec);
    }

    public function testKeepContainerOnErrorWithExistingContainer()
    {
        $containerId = 'face42face42';

        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', $containerId) # id for name of potential re-use
            ->expect('pass', '~ docker exec ~', 255)
        ;

        $this->keepContainerOnErrorExecTest($exec, $containerId);
    }

    public function testArtifacts()
    {
        $tmpProjectDir = $this->getTestProject();

        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', './build/foo-package.tgz')
            ->expect('pass', 'docker exec -w /app \'*dry-run*\' tar c -f - build/foo-package.tgz | tar x -f - -C ' . $tmpProjectDir, 0)
            ->expect('capture', 'docker', 0) # docker kill
            ->expect('capture', 'docker', 0) # docker rm
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $tmpProjectDir),
            $exec,
            Runner::FLAG_DEPLOY_COPY | Runner::FLAGS,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
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
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', './build/foo-package.tgz')
            ->expect('capture', 'docker', 0) # docker kill
            ->expect('capture', 'docker', 0) # docker rm
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAG_DEPLOY_COPY | Runner::FLAGS,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
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
        $tmpProjectDir = $this->getTestProject();

        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', './build/foo-package.tgz')
            ->expect(
                'pass',
                'docker exec -w /app \'*dry-run*\' tar c -f - build/foo-package.tgz | tar x -f - -C ' . $tmpProjectDir,
                1
            )
            ->expect('capture', 'docker', 0) # docker kill
            ->expect('capture', 'docker', 0) # docker rm
        ;

        $this->expectOutputRegex('~^pipelines: Artifact failure: \'build/foo-package.tgz\' \\(1, 1 paths, 1\\d\\d bytes\\)$~m');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $tmpProjectDir),
            $exec,
            Runner::FLAG_DEPLOY_COPY | Runner::FLAGS,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
                'artifacts' => array('build/foo-package.tgz'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(0, $status);
    }

    public function testZapExistingContainer()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', "123456789\n") # zap: docker ps
            ->expect('capture', 'docker', "123456789\n") # zap: docker kill
            ->expect('capture', 'docker', "123456789\n") # zap: docker rm
            ->expect('capture', 'docker', 0) # docker run
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', 0) # docker kill
            ->expect('capture', 'docker', 0) # docker rm
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            Runner::FLAG_DEPLOY_COPY | Runner::FLAGS,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(0, $status);
    }

    public function testKeepExistingContainer()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', "123456789\n") # existing id
            ->expect('pass', '~ docker exec ~', 0) # docker exec
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            (Runner::FLAG_DOCKER_KILL | Runner::FLAG_DOCKER_REMOVE) ^ Runner::FLAGS,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(0, $status);
    }

    public function testDockerHubImageLogin()
    {
        $exec = new Exec();
        $exec->setActive(false);

        $this->expectOutputString('');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            null,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => array(
                    'name' => 'foo/bar:latest',
                    'username' => 'user',
                    'password' => 'secret',
                ),
                'script' => array(':'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(0, $status);
    }

    private function getTestProject()
    {
        $project = LibFs::tmpDir('pipelines-test-suite.');

        $this->cleaners[] = DestructibleString::rmDir($project);

        return $project;
    }

    private function keepContainerOnErrorExecTest(ExecTester $exec, $id = '*dry-run*')
    {
        $expectedRegex = sprintf(
            '{script non-zero exit status: 255\nerror, keeping container id %s}',
            preg_quote($id, '{}')
        );
        $this->expectOutputRegex($expectedRegex);
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, $this->getTestProject()),
            $exec,
            Runner::FLAGS | Runner::FLAG_KEEP_ON_ERROR, # keep on error flag is important
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array('fatal me an error'),
            ))
        ));

        $status = $runner->run($pipeline);
        $this->assertSame(255, $status);
    }
}
