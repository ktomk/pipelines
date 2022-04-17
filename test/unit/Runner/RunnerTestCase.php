<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\File\ReferenceTypes;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Project;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Value\SideEffect\DestructibleString;
use Ktomk\Pipelines\Yaml\Yaml;

/**
 * @coversNothing
 */
class RunnerTestCase extends TestCase
{
    /**
     * @var array
     */
    protected $cleaners = array();

    /**
     * @var Project
     */
    private $testProject;

    /**
     * @param null|array $extra
     *
     * @return Step
     */
    protected function createTestStep(array $extra = null)
    {
        $struct = array(
            'image' => 'foo/bar:latest',
            'pipelines' => array(
                'default' => array(
                    array('step' => ((array)$extra) + array('script' => array(':'))),
                ),
            ),
        );

        $file = new File($struct);

        return $file->getById('default')->getSteps()->offsetGet(0);
    }

    /**
     * @param string $file
     * @param int $step
     * @param string $pipeline
     *
     * @return Step
     */
    protected function createTestStepFromFixture($file, $step = 1, $pipeline = ReferenceTypes::SEG_DEFAULT)
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
     * @param Project $project
     */
    protected function setTestProject(Project $project)
    {
        $this->testProject = $project;
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
