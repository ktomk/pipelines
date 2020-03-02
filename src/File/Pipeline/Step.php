<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\Artifacts;
use Ktomk\Pipelines\File\Image;
use Ktomk\Pipelines\File\ParseException;
use Ktomk\Pipelines\File\Pipeline;

class Step
{
    /**
     * @var array
     */
    private $step;

    /**
     * @var int number of the step, starting at one
     */
    private $index;

    /**
     * @var Pipeline
     */
    private $pipeline;

    /**
     * @var array step environment variables
     *   BITBUCKET_PARALLEL_STEP - zero-based index of the current step in the group, e.g. 0, 1, 2, ...
     *   BITBUCKET_PARALLEL_STEP_COUNT - total number of steps in the group, e.g. 5.
     */
    private $env;

    /**
     * Step constructor.
     *
     * @param Pipeline $pipeline
     * @param int $index
     * @param array $step
     * @param array $env [optional] environment variables in array notation for the new step
     */
    public function __construct(Pipeline $pipeline, $index, array $step, array $env = array())
    {
        // quick validation: image name
        Image::validate($step);

        // quick validation: script
        $this->parseScript($step);

        $this->pipeline = $pipeline;
        $this->index = $index;
        $this->step = $step;
        $this->env = $env;
    }

    /**
     * @throws \Ktomk\Pipelines\File\ParseException
     * @return null|Artifacts
     */
    public function getArtifacts()
    {
        return isset($this->step['artifacts'])
            ? new Artifacts($this->step['artifacts'])
            : null;
    }

    /**
     * @throws \Ktomk\Pipelines\File\ParseException
     * @return Image
     */
    public function getImage()
    {
        return isset($this->step['image'])
            ? new Image($this->step['image'])
            : $this->pipeline->getFile()->getImage();
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return isset($this->step['name'])
            ? (string)$this->step['name']
            : null;
    }

    /**
     * @return StepServices
     */
    public function getServices()
    {
        $services = isset($this->step['services']) ? $this->step['services'] : array();

        return new StepServices($this, $services);
    }

    /**
     * @return array|string[]
     */
    public function getScript()
    {
        return $this->step['script'];
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $image = $this->getImage();
        $image = null === $image ? '' : $image->jsonSerialize();

        return array(
            'name' => $this->getName(),
            'image' => $image,
            'script' => $this->getScript(),
            'artifacts' => $this->getArtifacts(),
        );
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return Pipeline
     * @codeCoverageIgnore
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }

    /**
     * @return array step container environment variables (e.g. parallel a step)
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Parse a step script section
     *
     * @param array $step
     * @throws \Ktomk\Pipelines\File\ParseException
     */
    private function parseScript(array $step)
    {
        if (!isset($step['script'])) {
            ParseException::__("'step' requires a script");
        }
        if (!is_array($step['script']) || !count($step['script'])) {
            ParseException::__("'script' requires a list of commands");
        }

        foreach ($step['script'] as $index => $line) {
            if (!is_scalar($line) && null !== $line) {
                ParseException::__(
                    sprintf(
                        "'script' requires a list of commands, step #%d is not a command",
                        $index
                    )
                );
            }
        }
    }
}
