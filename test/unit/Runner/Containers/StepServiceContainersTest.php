<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\Runner\Env;
use Ktomk\Pipelines\Runner\Flags;
use Ktomk\Pipelines\Runner\Runner;
use Ktomk\Pipelines\TestCase;

/**
 * Class StepServiceContainersTest
 *
 * @package Ktomk\Pipelines\Runner\Containers
 * @covers \Ktomk\Pipelines\Runner\Containers\StepServiceContainers
 */
class StepServiceContainersTest extends TestCase
{
    public function testCreation()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');

        $services = new StepServiceContainers($step, $runner);
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Containers\StepServiceContainers', $services);
    }

    public function testObtainNetworkWithoutServices()
    {
        list($step, $runner, $exec) = $this->createStepAndRunnerMocks(
            __DIR__ . '/../../../data/yml/bitbucket-pipelines.yml'
        );

        $services = new StepServiceContainers($step, $runner);
        self::assertSame(array(), $services->obtainNetwork());
    }

    public function testObtainNetworkWithService()
    {
        list($step, $runner, $exec) = $this->createStepAndRunnerMocks(
            __DIR__ . '/../../../data/yml/service-definitions.yml'
        );

        $exec->expect('capture', 'docker');

        $services = new StepServiceContainers($step, $runner);
        self::assertSame(array('--network', 'host'), $services->obtainNetwork());
    }

    public function testShutdown()
    {
        list($step, $runner, $exec) = $this->createStepAndRunnerMocks(
            __DIR__ . '/../../../data/yml/service-definitions.yml'
        );

        $exec->expect('capture', 'docker');
        $exec->expect('capture', 'docker');

        $services = new StepServiceContainers($step, $runner);
        $services->shutdown(0);
    }

    /**
     * @param string $path of pipelines yaml file
     *
     * @return array
     */
    private function createStepAndRunnerMocks($path)
    {
        $file = File::createFromFile($path);
        $step = $file->getDefault()->getSteps()->offsetGet(0);
        $runOpts = $this->createConfiguredMock('Ktomk\Pipelines\Runner\RunOpts', array(
            'getPrefix' => 'prefix',
        ));

        $runner = new Runner(
            $runOpts,
            $this->createMock('Ktomk\Pipelines\Runner\Directories'),
            $exec = new ExecTester($this),
            new Flags(),
            new Env(),
            new Streams()
        );

        return array($step, $runner, $exec);
    }
}
