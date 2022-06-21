<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Project;
use Ktomk\Pipelines\Runner\Docker\Binary\Repository;
use Ktomk\Pipelines\Runner\Docker\Binary\UnPackager;
use Ktomk\Pipelines\Utility\OptionsMock;
use Ktomk\Pipelines\Value\SideEffect\DestructibleString;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class StepRunnerTest
 *
 * @covers \Ktomk\Pipelines\Runner\StepRunner
 */
class StepRunnerTest extends RunnerTestCase
{
    /**
     * @var string fixture of command for deploy mode copy
     *
     * @see doSetUp for initialization
     */
    private $deploy_copy_cmd;
    private $deploy_copy_cmd_2;

    protected function doSetUp()
    {
        parent::doSetUp();

        // create container target directory "/app" by tar
        $this->deploy_copy_cmd = '~cd ' . sys_get_temp_dir() . '/pipelines-cp\.[^/]+/\. '
            . "&& echo 'app' | tar c -h -f - --no-recursion app "
            . "| docker  cp - '\\*dry-run\\*:/\\.'~";

        // copy over project files into container "/app" directory by tar
        $this->deploy_copy_cmd_2 = '~cd ' . sys_get_temp_dir() . '/pipelines-test-suite[^/]*/\. '
            . '&& tar c -f - . '
            . "| docker  cp - '\\*dry-run\\*:/app'~";
    }

    public function testCreation()
    {
        $stepRunner = new StepRunner(
            $this->createMock('Ktomk\Pipelines\Runner\Runner')
        );
        self::assertInstanceOf('Ktomk\Pipelines\Runner\StepRunner', $stepRunner);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\ImageLogin::loginImage
     */
    public function testFailOnContainerCreation()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 1);
        $exec->expect('capture', 'docker', 126);

        $step = $this->createTestStep();
        $runner = $this->createTestStepRunner($exec, null, array(null, 'php://output'));

        $this->expectOutputRegex('~pipelines: setting up the container failed~');
        $actual = $runner->runStep($step);
        self::assertNotSame(0, $actual);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Containers\StepContainer
     */
    public function testRunning()
    {
        /** @var Exec|MockObject $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('pass')->willReturn(0);
        $exec->method('capture')->willReturn(0);

        $step = $this->createTestStep();
        $runner = $this->createTestStepRunner($exec, null, 'php://output');

        $this->expectOutputRegex('{^\x1d\+\+\+ step #1\n}');
        $actual = $runner->runStep($step);
        self::assertSame(0, $actual);
    }

    public function testCopy()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'run step container')
            ->expect('pass', $this->deploy_copy_cmd, 0, 'copy deployment /app create')
            ->expect('pass', $this->deploy_copy_cmd_2, 0, 'copy deployment /app copy')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 0, 'run step script')
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm');

        $step = $this->createTestStep();
        $runner = $this->createTestStepRunner($exec, Flags::FLAG_DEPLOY_COPY | Flags::FLAGS, 'php://output');

        $this->expectOutputRegex('{^\x1D\+\+\+ copying files into container\.\.\.\n}m');

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    /**
     * this is the first stage, however in general deploy copy fails, too.
     *
     * the first stage is to create the container target directory to copy
     * into.
     */
    public function testCopyFails()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'run step container')
            ->expect('pass', $this->deploy_copy_cmd, 1);

        $step = $this->createTestStep();
        $runner = $this->createTestStepRunner($exec, Flags::FLAG_DEPLOY_COPY | Flags::FLAGS, array(null, 'php://output'));

        $this->expectOutputRegex('{^pipelines: deploy copy failure}');
        $status = $runner->runStep($step);
        self::assertSame(1, $status);
    }

    /**
     * second stage is to copy project files into the container target directory
     */
    public function testCopyFailsAtSecondStage()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'run step container')
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 1);

        $step = $this->createTestStep();
        $runner = $this->createTestStepRunner($exec, Flags::FLAG_DEPLOY_COPY | Flags::FLAGS, array(null, 'php://output'));

        $this->expectOutputRegex('{^pipelines: deploy copy failure}');
        $status = $runner->runStep($step);
        self::assertSame(1, $status);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Containers\StepContainer
     */
    public function testKeepContainerOnErrorWithNonExistentContainer()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'no id for name of potential re-use')
            ->expect('capture', 'docker', 0, 'run the container')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 255);

        $this->keepContainerOnErrorExecTest($exec);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Containers\StepContainer
     */
    public function testKeepContainerOnErrorWithExistingContainer()
    {
        $containerId = 'face42face42';

        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', $containerId) # id for name of potential re-use
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 255);

        $this->keepContainerOnErrorExecTest($exec, $containerId);
    }

    public function testZapExistingContainer()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', "123456789\n", 'zap: docker ps')
            ->expect('capture', 'docker', "123456789\n", 'zap: docker kill')
            ->expect('capture', 'docker', "123456789\n", 'zap: docker rm')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('pass', $this->deploy_copy_cmd, 0, 'deploy copy stage 1')
            ->expect('pass', $this->deploy_copy_cmd_2, 0, 'deploy copy stage 1')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm');

        $runner = $this->createTestStepRunner($exec, Flags::FLAG_DEPLOY_COPY | Flags::FLAGS);
        $step = $this->createTestStep();

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Containers\StepContainer
     */
    public function testKeepExistingContainer()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', "123456789\n", 'existing id')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 0);

        $runner = $this->createTestStepRunner($exec, (Flags::FLAG_DOCKER_KILL | Flags::FLAG_DOCKER_REMOVE) ^ Flags::FLAGS);
        $step = $this->createTestStep();

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    public function testDockerHubImageLogin()
    {
        $exec = new Exec();
        $exec->setActive(false);

        $step = $this->createTestStep(array(
            'image' => array(
                'name' => 'foo/bar:latest',
                'username' => 'user',
                'password' => 'secret',
            ),
        ));
        $runner = $this->createTestStepRunner($exec, null, array(null, 'php://output'), array());

        $this->expectOutputString('');
        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    public function testMountInCopyFails()
    {
        $exec = new Exec();
        $exec->setActive(false);

        $step = $this->createTestStep();
        $this->setTestProject(new Project('/app')); # fake test-directory as if being inside a container FIXME(tk): hard encoded /app
        $runner = $this->createTestStepRunner($exec, null, array(null, 'php://output'), array('PIPELINES_PARENT_CONTAINER_NAME' => 'foo'));

        $this->expectOutputString("pipelines: fatal: can not detect /app mount point\npipelines: setting up the container failed\n");
        $status = $runner->runStep($step);
        self::assertSame(1, $status, 'non-zero status as mounting not possible with mock');
    }

    public function testMountWithoutHostConfig()
    {
        $exec = new Exec();
        $exec->setActive(false);

        $step = $this->createTestStep();
        $this->setTestProject(new Project('/app')); # fake test-directory as if being inside a container FIXME(tk): hard encoded /app
        $runner = $this->createTestStepRunner($exec, null, array(null, 'php://output'), array());

        $this->expectOutputString('');
        $status = $runner->runStep($step);
        self::assertSame(0, $status, 'passed as detected as non-pip');
    }

    public function testWithHostConfig()
    {
        $exec = new Exec();
        $exec->setActive(false);

        $step = $this->createTestStep();
        $this->setTestProject(new Project('/app')); # fake test-directory as if being inside a container FIXME(tk): hard encoded /app
        $inherit = array(
            'PIPELINES_PARENT_CONTAINER_NAME' => 'foo',
            'PIPELINES_PIP_CONTAINER_NAME' => 'foo',
        );
        $runner = $this->createTestStepRunner($exec, null, array(null, 'php://output'), $inherit);

        $this->expectOutputString("pipelines: fatal: can not detect /app mount point\npipelines: setting up the container failed\n");
        $status = $runner->runStep($step);
        self::assertSame(1, $status, 'non-zero status as mounting not possible with mock');
    }

    /* docker socket tests */

    public function testRunStepWithoutMountingDockerSocket()
    {
        $exec = new Exec();
        $exec->setActive(false);

        $step = $this->createTestStep();
        $flags = Flags::FLAG_DOCKER_REMOVE | Flags::FLAG_DOCKER_KILL;
        $runner = $this->createTestStepRunner($exec, $flags, array(null, 'php://output'), array('PIPELINES_PARENT_CONTAINER_NAME' => 'foo'));

        $this->expectOutputString('');
        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    public function testRunStepWithDockerHostParameterDockerSocket()
    {
        $inherit = array(
            'DOCKER_HOST' => 'unix:///var/run/docker.sock',
        );

        $exec = new Exec();
        $exec->setActive(false);

        $step = $this->createTestStep();
        $this->setTestProject(new Project('/app')); # fake test-directory as if being inside a container FIXME(tk): hard encoded /app
        $runner = $this->createTestStepRunner($exec, null, array(null, 'php://output'), $inherit);

        $this->expectOutputString('');
        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    public function testRunStepWithPipDockerSocket()
    {
        $inherit = array(
            'DOCKER_HOST' => 'unix:///run/user/1000/docker.sock',
            'PIPELINES_PARENT_CONTAINER_NAME' => 'parent_container_name',
            'PIPELINES_PIP_CONTAINER_NAME' => 'parent_container_name',
        );

        $exec = new ExecTester($this);

        $step = $this->createTestStep();
        $runner = $this->createTestStepRunner($exec, null, array(null, 'php://output'), $inherit);

        $this->expectOutputString('');
        $exec->expect('capture', 'docker', 0, 'ps');

        $buffer = json_encode(array(array(
            'HostConfig' => array(
                'Binds' => array('/dev/null:' . $this->getTestProject()->getPath() . '/var/run/docker.sock'),
            ),
        )));
        $exec->expect('capture', 'docker', $buffer, 'obtain socket bind');
        $exec->expect('capture', 'docker', 0, 'obtain mount bind');
        $exec->expect('capture', 'docker', 0, 'run');
        $exec->expect('capture', 'docker', 1, 'test for /bin/bash');
        $exec->expect('pass', '~ docker exec ~', 0, 'script');
        $exec->expect('capture', 'docker', 0, 'kill');
        $exec->expect('capture', 'docker', 0, 'rm');

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    /* docker client tests */

    /**
     * Test to install the test-stub for docker client
     *
     * Previously tested for docker client injection (copy), now tests
     * with mounting (here: no mount, so install the test package)
     */
    public function testDockerClientInjection()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'no id for name of potential re-use')
            ->expect('capture', 'docker', 0, 'run the container')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 0, 'run step script')
            ->expect('capture', 'docker', 0, 'kill')
            ->expect('capture', 'docker', 0, 'rm');

        $testDirectories = new Directories($_SERVER, $this->getTestProject());

        $testRepository = $this->getMockBuilder('Ktomk\Pipelines\Runner\Docker\Binary\Repository')
            ->setConstructorArgs(array($exec, array(), UnPackager::fromDirectories($exec, $testDirectories)))
            ->setMethods(array('getPackageLocalBinary'))
            ->getMock();
        $testRepository->method('getPackageLocalBinary')->willReturn(__DIR__ . '/../../data/package/docker-test-stub');

        $runner = new Runner(
            RunOpts::create('pipelinesunittest'),
            $testDirectories,
            $exec,
            new Flags(),
            Env::createEx(),
            new Streams()
        );

        /** @var MockObject|StepRunner $mockRunner */
        $mockRunner = $this->getMockBuilder('Ktomk\Pipelines\Runner\StepRunner')
            ->setConstructorArgs(array(
                $runner,
                RunOpts::create('pipelinesunittest'),
                $testDirectories,
                $exec,
                new Flags(),
                Env::createEx(),
                new Streams(),
            ))
            ->setMethods(array('getDockerBinaryRepository'))
            ->getMock();
        $mockRunner->method('getDockerBinaryRepository')->willReturn($testRepository);

        $step = $this->createTestStep(array('services' => array('docker')));
        $actual = $mockRunner->runStep($step);
        self::assertSame(0, $actual);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\StepRunner::obtainDockerClientMount()
     */
    public function testDockerClientMount()
    {
        $inherit = array(
            'PIPELINES_PARENT_CONTAINER_NAME' => 'parent_container_name',
            'PIPELINES_PIP_CONTAINER_NAME' => 'parent_container_name',
        );

        $exec = new ExecTester($this);

        $step = $this->createTestStep(array('services' => array('docker')));
        $runner = $this->createTestStepRunner($exec, null, array(null, 'php://output'), $inherit);

        $this->expectOutputString('');
        $exec->expect('capture', 'docker', 0, 'ps');
        $buffer = json_encode(array(array(
            'HostConfig' => array(
                'Binds' => array('/dev/null:/usr/bin/docker:ro'),
            ),
        )));
        $exec->expect('capture', 'docker', 0, 'obtain socket bind');
        $exec->expect('capture', 'docker', $buffer, 'obtain client bind');
        $exec->expect('capture', 'docker', 0, 'obtain mount bind');
        $exec->expect('capture', 'docker', 0, 'run');
        $exec->expect('capture', 'docker', 1, 'test for /bin/bash');
        $exec->expect('pass', '~ docker exec ~', 0, 'script');
        $exec->expect('capture', 'docker', 0, 'kill');
        $exec->expect('capture', 'docker', 0, 'rm');

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    /**
     *
     */
    public function testDockerClientInjectInvalidPackage()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'no id for name of potential re-use');
        $testDirectories = new Directories($_SERVER, $this->getTestProject());

        $runner = new Runner(
            RunOpts::create('pipelinesunittest', 'wrong-package'),
            $testDirectories,
            $exec,
            new Flags(),
            Env::createEx(),
            new Streams()
        );

        /** @var MockObject|StepRunner $mockRunner */
        $mockRunner = $this->getMockBuilder('Ktomk\Pipelines\Runner\StepRunner')
            ->setConstructorArgs(array($runner))
            ->setMethods(null)
            ->getMock();

        $step = $this->createTestStep(array('services' => array('docker')));
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('not a readable file');
        $mockRunner->runStep($step);
        self::fail('an expected exception was not thrown');
    }

    /* artifact tests */

    public function testArtifacts()
    {
        $tmpProjectDir = $this->getTestProject()->getPath();

        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', './build/foo-package.tgz')
            ->expect('pass', 'docker exec -w /app \'*dry-run*\' tar c -f - build/foo-package.tgz | tar x -f - -C ' . $tmpProjectDir, 0)
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm');

        $step = $this->createTestStep(array('artifacts' => array('build/foo-package.tgz')));
        $runner = $this->createTestStepRunner($exec, Flags::FLAG_DEPLOY_COPY | Flags::FLAGS);

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    public function testArtifactsNoMatch()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', './build/foo-package.tgz')
            ->expect('capture', 'docker', 0) # docker kill
            ->expect('capture', 'docker', 0) # docker rm
        ;

        $step = $this->createTestStep(array('artifacts' => array('build/bar-package.tgz')));
        $runner = $this->createTestStepRunner($exec, Flags::FLAG_DEPLOY_COPY | Flags::FLAGS);

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    public function testArtifactsFailure()
    {
        $tmpProjectDir = $this->getTestProject()->getPath();

        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('pass', $this->deploy_copy_cmd, 0)
            ->expect('pass', $this->deploy_copy_cmd_2, 0)
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~ docker exec ~', 0)
            ->expect('capture', 'docker', './build/foo-package.tgz')
            ->expect(
                'pass',
                'docker exec -w /app \'*dry-run*\' tar c -f - build/foo-package.tgz | tar x -f - -C ' . $tmpProjectDir,
                1
            )
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm');

        $this->expectOutputRegex('~^pipelines: Artifact failure: \'build/foo-package.tgz\' \\(1, 1 paths, 1\\d\\d bytes\\)$~m');
        $step = $this->createTestStep(array('artifacts' => array('build/foo-package.tgz')));
        $runner = $this->createTestStepRunner($exec, Flags::FLAG_DEPLOY_COPY | Flags::FLAGS, array(null, 'php://output'));

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    /* public/new */

    public function testGetDockerBinaryRepository()
    {
        $exec = new ExecTester($this);

        $runner = new Runner(
            RunOpts::create('foo', Repository::PKG_TEST),
            new Directories($_SERVER, new Project('foo')),
            $exec,
            new Flags(),
            new Env(),
            new Streams()
        );

        $stepRunner = new StepRunner($runner);
        $actual = $stepRunner->getDockerBinaryRepository();
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Docker\Binary\Repository', $actual);
    }

    public function testAfterScript()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~<<\'SCRIPT\' docker exec ~', 0, 'script')
            ->expect('pass', '~<<\'SCRIPT\' docker exec ~', 0, 'after-script')
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm');

        $step = $this->createTestStepFromFixture('after-script.yml');
        $runner = $this->createTestStepRunner($exec, Flags::FLAGS, 'php://output');

        $this->expectOutputRegex('{^After script:}m');

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    public function testAfterScriptFailing()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~<<\'SCRIPT\' docker exec ~', 0, 'script')
            ->expect('pass', '~<<\'SCRIPT\' docker exec ~', 123, 'after-script')
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm');

        $step = $this->createTestStepFromFixture('after-script.yml');
        $runner = $this->createTestStepRunner($exec, Flags::FLAGS, array('php://output', 'php://output'));

        $this->expectOutputRegex('{^after-script non-zero exit status: 123$}m');

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    /**
     * @see StepRunner::obtainServicesNetwork()
     * @see StepRunner::shutdownServices()
     */
    public function testServicesObtainNetworkAndShutdown()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('capture', 'docker', 0, 'run services')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~<<\'SCRIPT\' docker exec ~', 0, 'script')
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm')
            ->expect('capture', 'docker', 0, 'service docker kill')
            ->expect('capture', 'docker', 0, 'service docker rm');

        $step = $this->createTestStepFromFixture('service-definitions.yml');
        $runner = $this->createTestStepRunner($exec, Flags::FLAGS, array('php://output', 'php://output'));

        $this->expectOutputRegex('~effective-image: redis$~m');
        self::assertSame(0, $runner->runStep($step));
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\CacheIo
     */
    public function testCaches()
    {
        $tmpProjectDir = $this->getTestProject()->getPath();
        $tmpHomeDir = $tmpProjectDir . '/home';

        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~<<\'SCRIPT\' docker exec ~', 0, 'script')
            ->expect('capture', '~^docker exec ~', 0, 'caches: map path')
            ->expect('capture', '~>.*/composer\.tar docker cp~', 0, 'caches: copy out')
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm');

        $step = $this->createTestStepFromFixture('cache.yml');
        $runner = $this->createTestStepRunner($exec, Flags::FLAGS, 'php://output', array('HOME' => $tmpHomeDir));

        $this->expectOutputRegex('{^ - composer ~/.composer/cache }m');

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\CacheIo
     */
    public function testCachesPrePopulated()
    {
        $tmpProjectDir = $this->getTestProject()->getPath();
        $tmpHomeDir = $tmpProjectDir . '/home';

        // fake caches file
        $tarDir = $tmpHomeDir . '/.cache/pipelines/caches/local-has-no-slug';
        LibFs::mkDir($tarDir);
        touch($tarDir . '/composer.tar');

        $exec = new ExecTester($this);
        $exec
            ->expect('capture', 'docker', 1, 'zap')
            ->expect('capture', 'docker', 0, 'docker run step container')
            ->expect('capture', '~^docker exec ~', 0, 'caches: map path')
            ->expect('capture', '~^docker exec .* mkdir -p ~', 0, 'caches: mkdir in container')
            ->expect('capture', '~<.*/composer\.tar docker cp ~', 0, 'caches: copy in')
            ->expect('capture', 'docker', 1, 'test for /bin/bash')
            ->expect('pass', '~<<\'SCRIPT\' docker exec ~', 0, 'script')
            ->expect('capture', '~^docker exec ~', 0, 'caches: map path')
            ->expect('capture', '~>.*/composer\.tar docker cp~', 0, 'caches: copy out')
            ->expect('capture', 'docker', 0, 'docker kill')
            ->expect('capture', 'docker', 0, 'docker rm');

        $step = $this->createTestStepFromFixture('cache.yml');
        $runner = $this->createTestStepRunner($exec, Flags::FLAGS, 'php://output', array('HOME' => $tmpHomeDir));

        $this->expectOutputRegex('{^ - composer ~/.composer/cache }m');

        $status = $runner->runStep($step);
        self::assertSame(0, $status);
    }

    private function keepContainerOnErrorExecTest(ExecTester $exec, $id = '*dry-run*')
    {
        $expectedRegex = sprintf(
            '{script non-zero exit status: 255\nerror, keeping container id %s}',
            preg_quote($id, '{}')
        );

        $runner = $this->createTestStepRunner($exec, Flags::FLAGS | Flags::FLAG_KEEP_ON_ERROR, array(null, 'php://output'));
        $step = $this->createTestStep(); # 'script' => array('fatal me an error') not necessary

        $this->expectOutputRegex($expectedRegex);
        $status = $runner->runStep($step);
        self::assertSame(255, $status);
    }

    /**
     * @param Exec $exec
     * @param int $flags [optional] to override default flags
     * @param null|array|string $outErr [optional]
     * @param array $inherit [optional] inherit from environment
     *
     * @return StepRunner
     */
    private function createTestStepRunner(Exec $exec, $flags = null, $outErr = null, array $inherit = array())
    {
        list($out, $err) = ((array)$outErr) + array(null, null);

        $testProject = $this->getTestProject()->getPath();

        $options = OptionsMock::create();
        $options->define('step.clone-path', '/app');

        $clonePath = $options->get('step.clone-path');

        if (($clonePath !== $testProject) && is_dir($testProject)) {
            // fake docker.sock file inside temporary test directory so it exists
            $value = $testProject . '/var';
            LibFs::mkDir($value);
            $value .= '/run';
            LibFs::mkDir($value);
            $value .= '/docker.sock';
            touch($value);
        } else {
            $value = LibTmp::tmpFilePut('');
            $this->cleaners[] = DestructibleString::rm($value);
        }

        $options->define('docker.socket.path', $value);

        $runOpts = new RunOpts('pipelinesunittest', $options);
        $directories = new Directories($inherit + $_SERVER, new Project($testProject));
        $flagsObject = new Flags($flags);
        $env = Env::createEx($inherit);
        $streams = new Streams(null, $out, $err);

        return new StepRunner(
            new Runner($runOpts, $directories, $exec, $flagsObject, $env, $streams)
        );
    }
}
