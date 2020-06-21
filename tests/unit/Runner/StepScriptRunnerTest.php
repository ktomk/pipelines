<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;

/**
 * Class StepScriptRunnerTest
 *
 * @covers \Ktomk\Pipelines\Runner\StepScriptRunner
 *
 * @package Ktomk\Pipelines\Runner
 */
class StepScriptRunnerTest extends RunnerTestCase
{
    public function testCreation()
    {
        $runner = $this->createConfiguredMock('Ktomk\Pipelines\Runner\Runner', array(
            'getExec' => $this->createMock('Ktomk\Pipelines\Cli\Exec'),
            'getStreams' => $this->createMock('Ktomk\Pipelines\Cli\Streams'),
        ));

        $scriptRunner = new StepScriptRunner($runner, '*mock-run*');
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\StepScriptRunner', $scriptRunner);

        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $step->method('getScript')->willReturn(array());
        $actual = StepScriptRunner::createRunStepScript($runner, '*mock-run*', $step);

        $this->assertNull($actual);
    }

    public function testRunStepScript()
    {
        $exec = new ExecTester($this);
        $streams = new Streams(null, 'php://output', 'php://output');

        $runner = $this->createConfiguredMock('Ktomk\Pipelines\Runner\Runner', array(
            'getExec' => $exec,
            'getStreams' => $streams,
        ));

        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('steps.yml');
        $exec->expect('pass', '~^<<\'SCRIPT\' ~');

        $actual = $scriptRunner->runStepScript($step);
        $this->assertSame(0, $actual);
    }

    public function testRunStepScriptAndAfterScript()
    {
        $exec = new ExecTester($this);
        $streams = new Streams(null, 'php://output', 'php://output');

        $runner = $this->createConfiguredMock('Ktomk\Pipelines\Runner\Runner', array(
            'getExec' => $exec,
            'getStreams' => $streams,
        ));

        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('after-script.yml');

        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 3);
        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 1);

        $this->expectOutputString('script non-zero exit status: 3
After script:
after-script non-zero exit status: 1
');

        $actual = $scriptRunner->runStepScript($step);
        $this->assertSame(3, $actual);
    }

    public function testRunStepScriptWithPipe()
    {
        $exec = new ExecTester($this);
        $streams = new Streams(null, 'php://output', 'php://output');

        $runner = $this->createConfiguredMock('Ktomk\Pipelines\Runner\Runner', array(
            'getExec' => $exec,
            'getStreams' => $streams,
        ));

        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('pipe.yml', 1, 'branches/develop');

        $exec->expect('pass', '~^<<\'SCRIPT\' ~');

        $actual = $scriptRunner->runStepScript($step);
        $this->assertSame(0, $actual);
    }

    public function testRunStepScriptWithAfterScriptPipe()
    {
        $exec = new ExecTester($this);
        $streams = new Streams(null, 'php://output', 'php://output');

        $runner = $this->createConfiguredMock('Ktomk\Pipelines\Runner\Runner', array(
            'getExec' => $exec,
            'getStreams' => $streams,
        ));

        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('after-script-pipe.yml', 1, 'default');

        $this->expectOutputString('After script:' . "\n");

        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 0, 'script');
        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 0, 'after-script');

        $actual = $scriptRunner->runStepScript($step);
        $this->assertSame(0, $actual);
    }
}
