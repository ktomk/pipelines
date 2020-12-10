<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\Runner\Env;
use Ktomk\Pipelines\Runner\RunOpts;
use Ktomk\Pipelines\TestCase;

/**
 * Class ServiceOptionsTest
 *
 * @package Ktomk\Pipelines\Utility
 * @covers \Ktomk\Pipelines\Utility\ServiceOptions
 */
class ServiceOptionsTest extends TestCase
{
    /**
     * @throws StatusException
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     */
    public function testNoOption()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/service-definitions.yml');
        $this->getTestBindings(array('command'), $file)->run();
        $this->addToAssertionCount(1);
    }

    /**
     * @throws StatusException
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     */
    public function testOptionMissingArgument()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/service-definitions.yml');

        $this->expectException('Ktomk\Pipelines\Cli\ArgsException');
        $this->expectExceptionMessage('option --service requires an argument');

        $this->getTestBindings(array('command', '--service'), $file)->run();
    }

    /**
     * @throws StatusException
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     */
    public function testOptionWithMissingService()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/service-definitions.yml');

        $this->expectException('Ktomk\Pipelines\Cli\ArgsException');
        $this->expectExceptionMessage('undefined service: foobar');

        $this->getTestBindings(array('command', '--service', 'foobar'), $file)->run();
    }

    /**
     * @throws StatusException
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     */
    public function testOptionWithService()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/service-definitions.yml');

        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('');
        $this->expectExceptionCode(0);

        $options = $this->getTestBindings(array('command', '--service', 'redis'), $file, $execTester);

        /** @var ExecTester $execTester */
        $execTester->expect('pass', 'docker', 0, 'docker run');
        $options->run();
    }

    /**
     * @param array $args
     * @param File $file
     * @param null|ExecTester $execTester
     *
     * @return ServiceOptions
     */
    private function getTestBindings(array $args, File $file, ExecTester &$execTester = null)
    {
        $execTester = new ExecTester($this);

        return ServiceOptions::bind(
            Args::create($args),
            new Streams(),
            $file,
            $execTester,
            Env::create(),
            RunOpts::create('prefix'),
            $this->createMock('Ktomk\Pipelines\Runner\Directories')
        );
    }
}
