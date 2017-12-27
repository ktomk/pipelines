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
        if (!isset($step['script'])) {
            ParseException::__("'step' requires a script");
        }

        if (!count($step['script']) || !is_array($step['script'])) {
            ParseException::__("'script' requires a list of commands");
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
