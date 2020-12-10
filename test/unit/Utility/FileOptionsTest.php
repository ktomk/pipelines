<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\TestCase;

/**
 * Class FileOptionsTest
 *
 * @covers \Ktomk\Pipelines\Utility\FileOptions
 */
class FileOptionsTest extends TestCase
{
    public function testCreation()
    {
        $args = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');

        $options = new FileOptions($args, $output, $file);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\FileOptions', $options);
    }

    public function testBind()
    {
        $args = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');

        $options = FileOptions::bind($args, $output, $file);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\FileOptions', $options);
    }

    public function provideTestRunArgs()
    {
        return array(
            array(array(), null),
            array(array('--list'), 0),
            array(array('--images'), 0),
            array(array('--show'), 0),
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

        $options = new FileOptions($args, $output, $file);

        try {
            $options->run();
            self::assertNull($expected);
        } catch (StatusException $e) {
            self::assertSame($expected, $e->getCode());
        }
    }

    public function testShowPipelines()
    {
        $arg = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');

        $options = new FileOptions($arg, $output, $file);
        $actual = $options->showPipelines($file);
        self::assertSame(0, $actual);
    }
}
