<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
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
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\RunnerOptions', $runner);

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
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\RunOpts', $actual);
    }

    /**
     * @throws ArgsException
     * @throws StatusException
     */
    public function testInvalidPrefix()
    {
        $args = Args::create(array('cmd', '--prefix', '123'));
        $this->setExpectedException('Ktomk\Pipelines\Cli\ArgsException', 'invalid prefix: \'123\'');
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
        $this->setExpectedException('Ktomk\Pipelines\Utility\StatusException', '', 0);
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
        $this->setExpectedExceptionRegExp(
            'Ktomk\Pipelines\Cli\ArgsException',
            '(\Q \'oh-so-much-fake\' given\E$)m',
            1
        );
        RunnerOptions::bind($args, $streams)->run();
    }
}
