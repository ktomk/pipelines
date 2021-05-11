<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\Runner\RunOpts;
use Ktomk\Pipelines\TestCase;

/**
 * Class StepScriptOptionTest
 *
 * @covers \Ktomk\Pipelines\Utility\StepScriptOption
 */
class StepScriptOptionTest extends TestCase
{
    public function testCreation()
    {
        $args = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
        $runOpts = RunOpts::create();

        $options = new StepScriptOption($args, $output, $file, $runOpts);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\StepScriptOption', $options);
    }

    public function testBind()
    {
        $args = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
        $runOpts = RunOpts::create();

        $options = StepScriptOption::bind($args, $output, $file, $runOpts);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\StepScriptOption', $options);
    }

    public function provideTestRunArgs()
    {
        return array(
            array(array(), null),
            array(array('--step-script'), 0),
            array(array('--step-script='), 0),
            array(array('--step-script=0'), 1),
            array(array('--step-script=1'), 0),
            array(array('--step-script=1:'), 0),
            array(array('--step-script=1:d'), 1),
            array(array('--step-script=99'), 1),
            array(array('--step-script=1:default'), 0),
            array(array('--step-script=default'), 0),
            array(array('--step-script=custom/dev/null'), 1),
        );
    }

    /**
     * @param array $arguments
     * @param int $expected
     * @dataProvider provideTestRunArgs
     */
    public function testRun(array $arguments, $expected)
    {
        $args = new Args(array_merge(array('test-cmd'), $arguments));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
        $runOpts = RunOpts::create();

        $options = new StepScriptOption($args, $output, $file, $runOpts);

        try {
            $options->run();
            self::assertNull($expected);
        } catch (StatusException $e) {
            self::assertSame($expected, $e->getCode());
        }
    }
}
