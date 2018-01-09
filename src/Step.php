<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\File\ParseException;

class Step
{
    /**
     * @var array
     */
    private $step;

    /**
     * @var Pipeline
     */
    private $pipeline;

    /**
     * Step constructor.
     * @param Pipeline $pipeline
     * @param array $step
     */
    public function __construct(Pipeline $pipeline, array $step)
    {
        // quick validation: image name
        File::validateImageName($step);

        // quick validation: script
        if (!isset($step['script'])) {
            ParseException::__("'step' requires a script");
        }
        if (!count($step['script']) || !is_array($step['script'])) {
            ParseException::__("'script' requires a list of commands");
        }

        foreach ($step['script'] as $index => $line) {
            if (!is_scalar($line) && !is_null($line)) {
                ParseException::__(
                    sprintf(
                        "'script' requires a list of commands, step #%d is not a command",
                        $index
                    )
                );
            }
        }

        $this->pipeline = $pipeline;
        $this->step = $step;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return isset($this->step['image'])
            ? (string)$this->step['image']
            : $this->pipeline->getFile()->getImage();
    }

    /**
     * @return string|null
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
}
