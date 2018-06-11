<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\Runner\Directories;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\Runner
 */
class RunnerTest extends UnitTestCase
{
    /**
     * @var string fixture of command for deploy mode copy
     * @see setUp for initialization
     */
    private $deploy_copy_cmd;
    private $deploy_copy_cmd_2;

    public static function setUpBeforeClass()
    {
        // this test-case operates on a (clean) temporary directory
        $testDirectory = sys_get_temp_dir() . '/pipelines-test-suite';
        if (is_dir($testDirectory)) {
            shell_exec('rm -rf "' . $testDirectory . '/"');
            mkdir($testDirectory);
        }
        parent::setUpBeforeClass();
    }

    protected function setUp()
    {
        parent::setUp();

        $this->deploy_copy_cmd = "cd " . sys_get_temp_dir() . "/pipelines/cp/. " .
            "&& echo 'app' | tar c -h -f - --no-recursion app " .
            "| docker  cp - '*dry-run*:/.'";

        $this->deploy_copy_cmd_2 = "cd " . sys_get_temp_dir() . "/pipelines-test-suite/. " .
            "&& tar c -f - . " .
            "| docker  cp - '*dry-run*:/app'";
    }

    public function testFailOnContainerCreation()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 1);
        $exec->expect('capture', 'docker', 126);

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');

        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $this->expectOutputRegex('~pipelines: setting up the container failed~');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
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
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $this->expectOutputRegex('{^\x1d\+\+\+ step #1\n}');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
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
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array());

        $exec = new Exec();
        $exec->setActive(false);
        $this->expectOutputRegex('~pipelines: pipeline with no step to execute~');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
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
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            null,
            $env,
            new Streams(null, null, 'php://output')
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
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('pass', 'docker', 0)
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
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
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
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
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
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
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', 'docker', 255)
        ;

        $this->expectOutputRegex('{script non-zero exit status: 255\nkeeping container id \*dry-run\*}');
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAGS | Runner::FLAG_KEEP_ON_ERROR, # keep on error flag is important
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, array(
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
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('pass', 'docker', 0)
            ->expect('capture', 'docker', "./build/foo-package.tgz")
            ->expect('pass', 'docker exec -w /app \'*dry-run*\' tar c -f - build/foo-package.tgz | tar x -f - -C ' . sys_get_temp_dir() . '/pipelines-test-suite', 0)
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
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
            ->expect('pass', 'docker', 0)
            ->expect('capture', 'docker', "./build/foo-package.tgz")
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
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
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1)
            ->expect('capture', 'docker', 0)
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('pass', 'docker', 0)
            ->expect('capture', 'docker', "./build/foo-package.tgz")
            ->expect('pass', 'docker exec -w /app \'*dry-run*\' tar c -f - build/foo-package.tgz | tar x -f - -C ' . sys_get_temp_dir() . '/pipelines-test-suite', 1)
        ;

        $this->expectOutputString("pipelines: Artifact failure: 'build/foo-package.tgz' (1, 1 paths)\n");
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
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
            ->expect('pass', 'docker', 0) # docker exec
        ;

        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            Runner::FLAG_DEPLOY_COPY,
            null,
            new Streams()
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
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

        $this->expectOutputString("");
        $runner = new Runner(
            'pipelines-unit-test',
            new Directories($_SERVER, sys_get_temp_dir() . '/pipelines-test-suite'),
            $exec,
            null,
            null,
            new Streams(null, null, 'php://output')
        );

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
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
}
