<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\TestCase;
use UnexpectedValueException;

/**
 * Class ValidationOptionsTest
 *
 * @package Ktomk\Pipelines\Utility
 * @covers \Ktomk\Pipelines\Utility\ValidationOptions
 */
class ValidationOptionsTest extends TestCase
{
    public function testCreation()
    {
        $args = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');

        $options = new ValidationOptions($args, $output, $file);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\ValidationOptions', $options);
    }

    public function testBind()
    {
        $args = new Args(array('test-cmd'));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');

        $options = ValidationOptions::bind($args, $output, $file);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\ValidationOptions', $options);
    }

    public function provideTestRunArgs()
    {
        return array(
            array(array(), null),
            array(array('--validate'), 0),
            array(array('--validate', '--validate'), 0),
            array(array('--validate=' . __DIR__ . '/../../../bitbucket-pipelines.yml'), 0),
            array(array('--validate=' . __DIR__ . '/../../data/yml/invalid/service-definitions.yml'), 1),
            array(array('--validate=' . __DIR__ . '/../../data/yml/invalid/pipeline-step.yml'), 1),
            array(array('--validate=' . __DIR__ . '/../../data/yml/yaml/error.yml'), 0),
        );
    }

    /**
     * @param array $arguments
     * @param int $expected
     *
     * @dataProvider provideTestRunArgs
     *
     * @throws \ReflectionException
     */
    public function testRun(array $arguments, $expected)
    {
        $args = new Args(array_merge(array('test-cmd'), $arguments));
        $output = new Streams();
        $file = File::createFromFile(__DIR__ . '/../../../bitbucket-pipelines.yml');

        $options = new ValidationOptions($args, $output, $file);

        try {
            $options->run();
            self::assertNull($expected);
        } catch (StatusException $e) {
            self::assertSame($expected, $e->getCode());
        } catch (UnexpectedValueException $e) {
            self::assertSame($expected, $e->getCode());
        }
    }
}
