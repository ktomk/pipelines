<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\DestructibleString;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Project;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Yaml\Yaml;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversNothing
 */
class RunnerTestCase extends TestCase
{
    /**
     * @var string fixture of command for deploy mode copy
     *
     * @see setUp for initialization
     */
    protected $deploy_copy_cmd;
    protected $deploy_copy_cmd_2;

    /**
     * @var array
     */
    protected $cleaners = array();

    /**
     * @var null|string
     */
    private $testProject;

    protected function setUp()
    {
        parent::setUp();

        // create container target directory "/app" by tar
        $this->deploy_copy_cmd = '~cd ' . sys_get_temp_dir() . '/pipelines-cp\.[^/]+/\. '
            . "&& echo 'app' | tar c -h -f - --no-recursion app "
            . "| docker  cp - '\\*dry-run\\*:/\\.'~";

        // copy over project files into container "/app" directory by tar
        $this->deploy_copy_cmd_2 = '~cd ' . sys_get_temp_dir() . '/pipelines-test-suite[^/]*/\. '
            . '&& tar c -f - . '
            . "| docker  cp - '\\*dry-run\\*:/app'~";
    }

    /**
     * @param null|array $extra
     *
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

        $pipeline->method('getSteps')->willReturn(
            new Pipeline\Steps($pipeline, array(array('step' => $stepArray)))
        );
        $pipelineSteps = $pipeline->getSteps();
        $step = $pipelineSteps[0];
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Step', $step, 'creating the test step failed');

        return $step;
    }

    /**
     * @param string $file
     * @param int $step
     * @param string $pipeline
     *
     * @return Step
     */
    protected function createTestStepFromFixture($file, $step = 1, $pipeline = 'default')
    {
        $path = __DIR__ . '/../../data/yml/' . $file;
        $array = Yaml::file($path);
        $fileObject = new File($array);
        $pipelineObject = $fileObject->getById($pipeline);
        self::assertNotNull($pipelineObject);
        $pipelineSteps = $pipelineObject->getSteps();
        self::assertArrayHasKey($step - 1, $pipelineSteps);

        return $pipelineSteps[$step - 1];
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

        return $this->testProject = new Project($project);
    }
}
