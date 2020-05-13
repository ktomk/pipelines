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
        $runner = new StepScriptRunner();
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\StepScriptRunner', $runner);

        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $step->method('getScript')->willReturn(array());
        $actual = StepScriptRunner::createRunStepScript(
            $step,
            $this->createMock('Ktomk\Pipelines\Cli\Streams'),
            $this->createMock('Ktomk\Pipelines\Cli\Exec'),
            '*mock-run*'
        );
        $this->assertNull($actual);
    }

    public function testRunStepScript()
    {
        $runner = new StepScriptRunner();

        $step = $this->createTestStepFromFixture('steps.yml');
        $exec = new ExecTester($this);
        $streams = new Streams(null, 'php://output', 'php://output');

        $exec->expect('pass', '~^<<\'SCRIPT\' ~');

        $actual = $runner->runStepScript($step, $streams, $exec, '*test-run*');
        $this->assertSame(0, $actual);
    }

    public function testRunStepScriptAndAfterScript()
    {
        $runner = new StepScriptRunner();

        $step = $this->createTestStepFromFixture('after-script.yml');
        $exec = new ExecTester($this);
        $streams = new Streams(null, 'php://output', 'php://output');

        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 3);
        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 1);

        $this->expectOutputString('script non-zero exit status: 3
After script:
after-script non-zero exit status: 1
');

        $actual = $runner->runStepScript($step, $streams, $exec, '*test-run*');
        $this->assertSame(3, $actual);
    }
}
