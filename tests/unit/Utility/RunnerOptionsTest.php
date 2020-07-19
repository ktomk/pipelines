<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Utility\RunnerOptions
 */
class RunnerOptionsTest extends TestCase
{
    public function testCreation()
    {
        $args = Args::create(array('cmd', '--error-keep', '--prefix', 'prefix', '--docker-client', __FILE__));
        $runner = RunnerOptions::bind($args, Streams::create());
        self::assertInstanceOf('Ktomk\Pipelines\Utility\RunnerOptions', $runner);

        return $runner;
    }

    /**
     * @depends testCreation
     *
     * @param RunnerOptions $runner
     *
     * @throws ArgsException
     * @throws StatusException
     */
    public function testParse(RunnerOptions $runner)
    {
        $actual = $runner->run();
        self::assertInstanceOf('Ktomk\Pipelines\Runner\RunOpts', $actual);
    }

    /**
     * @throws ArgsException
     * @throws StatusException
     */
    public function testInvalidPrefix()
    {
        $args = Args::create(array('cmd', '--prefix', '123'));
        $this->expectException('Ktomk\Pipelines\Cli\ArgsException');
        $this->expectExceptionMessage('invalid prefix: \'123\'');
        RunnerOptions::bind($args, Streams::create())->run();
    }

    /**
     * @throws ArgsException
     * @throws StatusException
     */
    public function testListPackages()
    {
        $args = Args::create(array('cmd', '--docker-client-pkgs'));
        $streams = new Streams(null, 'php://output', null);
        $this->expectOutputRegex('(^\Qdocker-42.42.1-binsh-test-stub\E$)m');
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('');
        $this->expectExceptionCode(0);
        RunnerOptions::bind($args, $streams)->run();
    }

    /**
     * @throws ArgsException
     * @throws StatusException
     */
    public function testListPackagesOnInvalidClient()
    {
        $args = Args::create(array('cmd', '--docker-client', 'oh-so-much-fake'));
        $streams = new Streams(null, 'php://output', null);
        $this->expectException(
            'Ktomk\Pipelines\Cli\ArgsException'
        );
        $this->expectExceptionMessageMatches(
            '(\Q \'oh-so-much-fake\' given\E$)m'
        );
        $this->expectExceptionCode(
            1
        );
        RunnerOptions::bind($args, $streams)->run();
    }

    /**
     * @throws ArgsException
     * @throws StatusException
     */
    public function testUserOptionSet()
    {
        $args = Args::create(array('cmd', '--user', '--user')); # two tests

        $streams = new Streams(null, 'php://output', 'php://output');

        $exec = new ExecTester($this);
        $runnerOptions = new RunnerOptions($args, $streams, $exec);

        $exec->expect('capture', '~^printf ~', 0);

        $runOpts = $runnerOptions->run();
        self::assertIsString($runOpts->getUser());

        $exec->expect('capture', '~^printf ~', 1);

        $this->expectException('Ktomk\Pipelines\Cli\ArgsException');
        $this->expectExceptionMessage('--user internal error to resolve id -u / id -g: 1');

        $runOpts = $runnerOptions->run();
        $runOpts->getUser();
    }
}
