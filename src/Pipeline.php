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
     * @var array|Step[]
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
            ParseException::__("Pipeline requires a list of steps");
        }

        $this->file = $file;
        $this->steps = $this->parseSteps($definition);
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
     * @return array|Step[]
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
        $steps = array();
        foreach ($this->steps as $step) {
            $steps[] = $step->jsonSerialize();
        }

        return array(
            'steps' => $steps,
        );
    }

    /**
     * @param array $definition
     * @throws \Ktomk\Pipelines\File\ParseException
     * @return array
     */
    private function parseSteps(array $definition) {
        $steps = array();
        foreach ($definition as $index => $step) {
            if (!is_array($step)) {
                ParseException::__("Pipeline requires a list of steps");
            }
            $steps[] = $this->step($index, $step);
        }

        return $steps;
    }

    /**
     * @param int $index of step, from the zero based index in the list of steps
     * @param array $step
     * @return Step
     */
    private function step($index, array $step)
    {
        if (!isset($step['step'])) {
            ParseException::__(
                "Missing required property 'step'"
            );
        }

        return new Step($this, $index, $step['step']);
    }
}
