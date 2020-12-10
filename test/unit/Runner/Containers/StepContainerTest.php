<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Runner\Containers;
use Ktomk\Pipelines\Runner\Env;
use Ktomk\Pipelines\Runner\RunnerTestCase;
use Ktomk\Pipelines\Runner\RunOpts;

/**
 * Class StepContainerTest
 *
 * @package Ktomk\Pipelines\Runner
 * @covers \Ktomk\Pipelines\Runner\Containers\StepContainer
 */
class StepContainerTest extends RunnerTestCase
{
    public function testCreation()
    {
        $step = $this->getStepMock();

        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getExec')->willReturn(new Exec());

        $container = new StepContainer('test-step-container', $step, $runner);
        self::assertNotNull($container);
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Containers\StepContainer', $container);
    }

    public function testGetName()
    {
        $expected = 'pipelines-1.no-name.null.test-project';

        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getExec')->willReturn(new ExecTester($this));

        $container = new StepContainer($expected, $this->getStepMock(), $runner);
        $actual = $container->getName();
        self::assertSame($expected, $actual);
    }

    public function testKeepOrKill()
    {
        $exec = new ExecTester($this);
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getExec')->willReturn($exec);

        $name = 'pipelines-1.no-name.null.test-project';
        $container = new StepContainer($name, $this->getStepMock(), $runner);

        $exec->expect('capture', 'docker');
        self::assertNull($container->keepOrKill(false));

        $exec->expect('capture', 'docker', '1234567');
        self::assertSame('1234567', $container->keepOrKill(true));

        self::assertSame('1234567', $container->getId());
    }

    public function testKillAndRemoveThrowsNot()
    {
        $exec = new ExecTester($this);
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getExec')->willReturn($exec);

        $container = new StepContainer('test-step-container', $this->getStepMock(), $runner);

        $container->killAndRemove(false, false);
        $this->addToAssertionCount(1);

        $exec->expect('capture', 'docker', 0, 'rm');
        $container->killAndRemove(false, true);
        $this->addToAssertionCount(1);
    }

    public function testKillAndRemove()
    {
        $exec = new ExecTester($this);
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getExec')->willReturn($exec);

        $name = 'pipelines-1.no-name.null.test-project';
        $container = new StepContainer($name, $this->getStepMock(), $runner);

        $exec->expect('capture', 'docker', '1234567', 'fake container id');
        $container->keepOrKill(true);

        $exec->expect('capture', 'docker');
        $exec->expect('capture', 'docker');
        $container->killAndRemove(true, true);
    }

    public function testRun()
    {
        $exec = new ExecTester($this);
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getExec')->willReturn($exec);

        $container = new StepContainer('test-step-container', $this->getStepMock(), $runner);
        self::assertNull($container->getId(), 'precondition');

        $exec->expect('capture', 'docker', '1234567', 'run');
        $actual = $container->run(array());
        self::assertIsArray($actual);
        self::assertCount(3, $actual);
        self::assertSame('1234567', $container->getId());
        self::assertSame('1234567', $container->getDisplayId());
    }

    public function testRunDryRun()
    {
        $exec = new ExecTester($this);
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getExec')->willReturn($exec);
        $container = new StepContainer('test-step-container', $this->getStepMock(), $runner);
        self::assertNull($container->getId(), 'precondition');

        $exec->expect('capture', 'docker', '', 'run');
        $actual = $container->run(array());
        self::assertIsArray($actual);
        self::assertCount(3, $actual);
        self::assertNull($container->getId());
        self::assertSame('*dry-run*', $container->getDisplayId());
    }

    public function testExecRunServiceContainerAttached()
    {
        $exec = new ExecTester($this);
        $exec
            ->expect('pass', 'docker', 0, 'docker run service');

        $step = $this->createTestStepFromFixture('service-definitions.yml');
        list($first) = $step->getServices()->getServiceNames();
        $service = $step->getFile()->getDefinitions()->getServices()->getByName($first);

        $actual = Containers::execRunServiceContainerAttached(
            $exec,
            $service,
            function ($a) {
                return $a;
            },
            'prefix',
            'project',
            array()
        );
        $expected = array(0, array('--network', 'host'));
        self::assertSame($expected, $actual);

        $messages = $exec->getDebugMessages();
        self::assertCount(1, $messages);
        self::assertStringContainsString(' --rm ', $messages[0]);
        self::assertStringNotContainsString(' --detached ', $messages[0]);
        self::assertStringNotContainsString(' -d ', $messages[0]);
    }

    public function testObtainUserOptions()
    {
        $step = $this->getStepMock();
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runOpts = new RunOpts();
        $runner->method('getRunOpts')->willReturn($runOpts);

        $container = new StepContainer('name', $step, $runner);

        $expected = array();
        self::assertSame($expected, $container->obtainUserOptions(), 'no user option');

        $runOpts->setUser('1000:1000');

        $expected = array(
            0 => '--user',
            1 => '1000:1000',
            2 => '-v',
            3 => '/etc/passwd:/etc/passwd:ro',
            4 => '-v',
            5 => '/etc/group:/etc/group:ro',
        );
        self::assertSame($expected, $container->obtainUserOptions(), 'user option');
    }

    public function testObtainLabelOptions()
    {
        $step = $this->getStepMock();
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runOpts = new RunOpts();
        $runner->method('getRunOpts')->willReturn($runOpts);
        $directories = $this->createMock('Ktomk\Pipelines\Runner\Directories');
        $runner->method('getDirectories')->willReturn($directories);
        $runner->method('getPrefix')->willReturn('pipelines');

        $container = new StepContainer('name', $step, $runner);

        $expected = array(
            0 => '-l',
            1 => 'pipelines.prefix=',
            2 => '-l',
            3 => 'pipelines.role=step',
            4 => '-l',
            5 => 'pipelines.project.name',
            6 => '-l',
            7 => 'pipelines.project.path',
        );

        self::assertSame($expected, $container->obtainLabelOptions(), 'label options');
    }

    public function testSshOption()
    {
        $step = $this->getStepMock();
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runOpts = new RunOpts('foo');
        $env = new Env();
        $runner->method('getEnv')->willReturn($env);
        $runner->method('getRunOpts')->willReturn($runOpts);

        $container = new StepContainer('name', $step, $runner);

        $actual = $container->obtainSshOptions();
        self::assertSame(array(), $actual, 'no ssh option');

        $runOpts->setSsh(true);
        list($handle, $file) = LibTmp::tmpFile();
        $env->initDefaultVars(array('SSH_AUTH_SOCK' => $file));
        $actual = $container->obtainSshOptions();
        $expected = array(
            0 => '-v',
            1 => $file . ':/var/run/ssh-auth.sock:ro',
            2 => '-e',
            3 => 'SSH_AUTH_SOCK=/var/run/ssh-auth.sock',
        );
        self::assertSame($expected, $actual, 'ssh option');
        unset($handle);
    }

    /**
     * @return \Ktomk\Pipelines\File\Pipeline\Step|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getStepMock()
    {
        $step = $this->createPartialMock(
            'Ktomk\Pipelines\File\Pipeline\Step',
            array('getPipeline')
        );
        $step->method('getPipeline')
            ->willReturn(
                $this->createMock('Ktomk\Pipelines\File\Pipeline')
            );

        return $step;
    }
}
