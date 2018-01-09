<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;


use Ktomk\Pipelines\File\ParseException;

class Pipeline
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var array
     */
    private $steps;

    public function __construct(File $file, array $definition)
    {
        // quick validation
        if (!isset($definition[0])) {
            ParseException::__("Pipeline requires a list of steps");
        }

        $this->file = $file;
        $this->steps = $this->parseSteps($definition);
    }

    /**
     * @param array $definition
     * @return array
     */
    private function parseSteps(array $definition) {
        $steps = array();
        foreach ($definition as $index => $step) {
            if (!is_array($step)) {
                ParseException::__("Pipeline requires a list of steps");
            }
            $steps[] = $this->step($step);
        }
        return $steps;
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
     * @return string|null id, can be null in fake/test conditions
     */
    public function getId()
    {
        return $this->file->getIdFrom($this);
    }

    /**
     * @return array|Step[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    private function step(array $step)
    {
        if (!isset($step['step'])) {
            ParseException::__(
                "Missing required property 'step'"
            );
        }

        return new Step($this, $step['step']);
    }

}
