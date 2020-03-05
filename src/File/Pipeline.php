<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\File\Pipeline\Steps;

class Pipeline
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var Steps
     */
    private $steps;

    /**
     * Pipeline constructor.
     * @param File $file
     * @param array $definition
     * @throws \Ktomk\Pipelines\File\ParseException
     */
    public function __construct(File $file, array $definition)
    {
        // quick validation
        if (!isset($definition[0])) {
            ParseException::__('Pipeline requires a list of steps');
        }

        $this->file = $file;
        $this->steps = new Steps($this, $definition);
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * get id of pipeline within the corresponding file object
     *
     * @return null|string id, can be null in fake/test conditions
     */
    public function getId()
    {
        return $this->file->getIdFrom($this);
    }

    /**
     * @return Step[]|Steps
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->steps->jsonSerialize();
    }
}
