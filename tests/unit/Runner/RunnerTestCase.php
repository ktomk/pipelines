<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\DestructibleString;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\File\Step;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversNothing
 */
class RunnerTestCase extends TestCase
{
    /**
     * @var string fixture of command for deploy mode copy
     * @see setUp for initialization
     */
    protected $deploy_copy_cmd;
    protected $deploy_copy_cmd_2;

    /**
     * @var array
     */
    private $cleaners = array();

    /**
     * @var null|string
     */
    private $testProject;

    protected function setUp()
    {
        parent::setUp();

        // create container target directory "/app" by tar
        $this->deploy_copy_cmd = '~cd ' . sys_get_temp_dir() . '/pipelines-cp\.[^/]+/\. ' .
            "&& echo 'app' | tar c -h -f - --no-recursion app " .
            "| docker  cp - '\\*dry-run\\*:/\\.'~";

        // copy over project files into container "/app" directory by tar
        $this->deploy_copy_cmd_2 = '~cd ' . sys_get_temp_dir() . '/pipelines-test-suite[^/]*/\. ' .
            '&& tar c -f - . ' .
            "| docker  cp - '\\*dry-run\\*:/app'~";
    }

    /**
     * @param null|array $extra
     * @return Step
     */
    protected function createTestStep(array $extra = null)
    {
        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');

        $stepArray = ((array)$extra) + array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            );

        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, 0, $stepArray)
        ));
        $pipelineSteps = $pipeline->getSteps();
        $step = $pipelineSteps[0];
        $this->assertInstanceOf('Ktomk\Pipelines\File\Step', $step, 'creating the test step failed');

        return $step;
    }

    /**
     * @param string $dir
     */
    protected function setTestProject($dir)
    {
        $this->testProject = $dir;
    }

    protected function getTestProject()
    {
        if (null !== $this->testProject) {
            return $this->testProject;
        }

        $project = LibTmp::tmpDir('pipelines-test-suite.');
        $this->cleaners[] = DestructibleString::rmDir($project);

        return $this->testProject = $project;
    }
}
