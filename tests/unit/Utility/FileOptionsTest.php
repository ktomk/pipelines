<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File;
use PHPUnit\Framework\TestCase;

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
        $file = File::createFromFile(__DIR__ . '/../../data/bitbucket-pipelines.yml');

        $options = new FileOptions($args, $output, $file);
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\FileOptions', $options);
    }

    public function testBind()
    {
        $args = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/bitbucket-pipelines.yml');

        $options = FileOptions::bind($args, $output, $file);
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\FileOptions', $options);
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
     * @param string $args
     * @param int $expected
     * @dataProvider provideTestRunArgs
     */
    public function testRun($args, $expected)
    {
        $args = new Args(array_merge(array('test-cmd'), $args));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/bitbucket-pipelines.yml');

        $options = new FileOptions($args, $output, $file);
        $actual = $options->run();
        $this->assertSame($expected, $actual);
    }

    public function testShowPipelines()
    {
        $arg = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/bitbucket-pipelines.yml');

        $options = new FileOptions($arg, $output, $file);
        $actual = $options->showPipelines($file);
        $this->assertSame(0, $actual);
    }
}
