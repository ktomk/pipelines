<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;

/**
 * Class StepContainerTest
 *
 * @package Ktomk\Pipelines\Runner
 * @covers \Ktomk\Pipelines\Runner\StepContainer
 */
class StepContainerTest extends RunnerTestCase
{
    public function testCreation()
    {
        $step = $this->getStepMock();

        $container = new StepContainer('test-step-container', $step, new Exec());
        $this->assertNotNull($container);
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\StepContainer', $container);
    }

    public function testGetName()
    {
        $expected = 'pipelines-1.no-name.null.test-project';
        $container = new StepContainer($expected, $this->getStepMock(), new ExecTester($this));
        $actual = $container->getName();
        $this->assertSame($expected, $actual);
    }

    public function testKeepOrKillThrowsException()
    {
        $container = new StepContainer(null, $this->getStepMock(), new ExecTester($this));

        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage('Container has no name yet');
        $container->keepOrKill(false);
    }

    public function testKeepOrKill()
    {
        $exec = new ExecTester($this);
        $name = 'pipelines-1.no-name.null.test-project';
        $container = new StepContainer($name, $this->getStepMock(), $exec);

        $exec->expect('capture', 'docker');
        $this->assertNull($container->keepOrKill(false));

        $exec->expect('capture', 'docker', '1234567');
        $this->assertSame('1234567', $container->keepOrKill(true));

        $this->assertSame('1234567', $container->getId());
    }

    public function testKillAndRemoveThrowsNot()
    {
        $exec = new ExecTester($this);
        $container = new StepContainer('test-step-container', $this->getStepMock(), $exec);

        $container->killAndRemove(false, false);
        $this->addToAssertionCount(1);

        $exec->expect('capture', 'docker', 0, 'rm');
        $container->killAndRemove(false, true);
        $this->addToAssertionCount(1);
    }

    public function testKillAndRemove()
    {
        $exec = new ExecTester($this);
        $name = 'pipelines-1.no-name.null.test-project';
        $container = new StepContainer($name, $this->getStepMock(), $exec);

        $exec->expect('capture', 'docker', '1234567', 'fake container id');
        $container->keepOrKill(true);

        $exec->expect('capture', 'docker');
        $exec->expect('capture', 'docker');
        $container->killAndRemove(true, true);
    }

    public function testRun()
    {
        $exec = new ExecTester($this);
        $container = new StepContainer('test-step-container', $this->getStepMock(), $exec);
        $this->assertNull($container->getId(), 'precondition');

        $exec->expect('capture', 'docker', '1234567', 'run');
        $actual = $container->run(array());
        self::assertIsArray($actual);
        $this->assertCount(3, $actual);
        $this->assertSame('1234567', $container->getId());
        $this->assertSame('1234567', $container->getDisplayId());
    }

    public function testRunDryRun()
    {
        $exec = new ExecTester($this);
        $container = new StepContainer('test-step-container', $this->getStepMock(), $exec);
        $this->assertNull($container->getId(), 'precondition');

        $exec->expect('capture', 'docker', '', 'run');
        $actual = $container->run(array());
        self::assertIsArray($actual);
        $this->assertCount(3, $actual);
        $this->assertNull($container->getId());
        $this->assertSame('*dry-run*', $container->getDisplayId());
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
            'project'
        );
        $expected = array(0, array('--network', 'host'));
        $this->assertSame($expected, $actual);

        $messages = $exec->getDebugMessages();
        $this->assertCount(1, $messages);
        self::assertStringContainsString(' --rm ', $messages[0]);
        self::assertStringNotContainsString(' --detached ', $messages[0]);
        self::assertStringNotContainsString(' -d ', $messages[0]);
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
