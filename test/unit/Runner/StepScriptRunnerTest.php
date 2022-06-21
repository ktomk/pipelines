<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
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
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $runner = $this->mockRunner($exec);

        $scriptRunner = new StepScriptRunner($runner, '*mock-run*');
        self::assertInstanceOf('Ktomk\Pipelines\Runner\StepScriptRunner', $scriptRunner);

        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $step->method('getScript')->willReturn(array());

        $this->expectOutputRegex('~^\Qscript non-zero exit status: 0\E$~'); # null to 0 conversion
        $actual = StepScriptRunner::createRunStepScript($runner, '*mock-run*', $step);

        self::assertNull($actual);
    }

    public function testRunStepScript()
    {
        $runner = $this->mockRunner($exec);
        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('steps.yml');
        $exec->expect('capture', 'docker', 1, 'test for /bin/bash');
        $exec->expect('pass', '~^<<\'SCRIPT\' ~');

        $actual = $scriptRunner->runStepScript($step);
        self::assertSame(0, $actual);
    }

    public function testRunStepScriptWithBinBash()
    {
        $runner = $this->mockRunner($exec);
        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('steps.yml');
        $exec->expect('capture', 'docker', 0, 'test for /bin/bash');
        $exec->expect('pass', '~^<<\'SCRIPT\' ~');

        $actual = $scriptRunner->runStepScript($step);
        self::assertSame(0, $actual);
    }

    public function testRunStepScriptAndAfterScript()
    {
        $runner = $this->mockRunner($exec);
        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('after-script.yml');

        $exec->expect('capture', 'docker', 1, 'test for /bin/bash');
        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 3);
        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 1);

        $this->expectOutputString('script non-zero exit status: 3
After script:
after-script non-zero exit status: 1
');

        $actual = $scriptRunner->runStepScript($step);
        self::assertSame(3, $actual);
    }

    public function testRunStepScriptWithPipe()
    {
        $runner = $this->mockRunner($exec);
        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('pipe.yml', 1, 'branches/develop');

        $exec->expect('capture', 'docker', 1, 'test for /bin/bash');
        $exec->expect('pass', '~^<<\'SCRIPT\' ~');

        $actual = $scriptRunner->runStepScript($step);
        self::assertSame(0, $actual);
    }

    public function testRunStepScriptWithAfterScriptPipe()
    {
        $runner = $this->mockRunner($exec);

        $scriptRunner = new StepScriptRunner($runner, '*test-run*');

        $step = $this->createTestStepFromFixture('after-script-pipe.yml', 1, 'default');

        $this->expectOutputString('After script:' . "\n");

        $exec->expect('capture', 'docker', 1, 'test for /bin/bash');
        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 0, 'script');
        $exec->expect('pass', '~^<<\'SCRIPT\' ~', 0, 'after-script');

        $actual = $scriptRunner->runStepScript($step);
        self::assertSame(0, $actual);
    }

    /**
     * @param Exec|ExecTester $exec
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|Runner
     */
    private function mockRunner(Exec &$exec = null)
    {
        if (null === $exec) {
            $exec = new ExecTester($this);
        }

        $streams = new Streams(null, 'php://output', 'php://output');

        return $this->createConfiguredMock('Ktomk\Pipelines\Runner\Runner', array(
            'getExec' => $exec,
            'getStreams' => $streams,
            'getRunOpts' => RunOpts::create('unittestpipelines'),
        ));
    }
}
