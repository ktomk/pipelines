<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\File\Artifacts;
use Ktomk\Pipelines\File\Image;
use Ktomk\Pipelines\File\ParseException;

class Step
{
    /**
     * @var array
     */
    private $step;

    /**
     * @var int numer of the step, starting at one
     */
    private $index;

    /**
     * @var Pipeline
     */
    private $pipeline;

    /**
     * Step constructor.
     * @param Pipeline $pipeline
     * @param int $index
     * @param array $step
     */
    public function __construct(Pipeline $pipeline, $index, array $step)
    {
        // quick validation: image name
        File::validateImage($step);

        // quick validation: script
        $this->parseScript($step);

        $this->pipeline = $pipeline;
        $this->index = $index;
        $this->step = $step;
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
        $image = null === $image ? "" : $image->jsonSerialize();

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
