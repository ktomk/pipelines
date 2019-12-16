<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Utility\RunnerOptions
 */
class RunnerOptionsTest extends TestCase
{
    public function testCreation()
    {
        $args = Args::create(array('cmd', '--error-keep', '--prefix', 'prefix', '--docker-client', 'test'));
        $runner = RunnerOptions::bind($args);
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\RunnerOptions', $runner);

        return $runner;
    }

    /**
     * @depends testCreation
     * @param RunnerOptions $runner
     * @throws ArgsException
     */
    public function testParse(RunnerOptions $runner)
    {
        $actual = $runner->run();
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\RunOpts', $actual);
    }

    /**
     * @throws ArgsException
     */
    public function testInvalidPrefix()
    {
        $args = Args::create(array('cmd', '--prefix', '123'));
        $this->setExpectedException('Ktomk\Pipelines\Cli\ArgsException', 'invalid prefix: \'123\'');
        RunnerOptions::bind($args)->run();
    }
}
