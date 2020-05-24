<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\File\Pipeline\Steps;
use Ktomk\Pipelines\Value\StepExpression;

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
     *
     * @param File $file
     * @param array $definition
     *
     * @throws ParseException
     */
    public function __construct(File $file, array $definition)
    {
        // quick validation
        if (!isset($definition[0])) {
            throw new ParseException('Pipeline requires a list of steps');
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
     * @param string $expression [optional]
     *
     * @return void
     */
    public function setStepsExpression($expression = null)
    {
        if (null === $expression) {
            return;
        }

        // parse, resolve early
        $array = StepExpression::createFromString($expression)
            ->resolveSteps($this->getSteps());

        $this->steps->setGetIteratorFunctor(function () use ($array) {
            return new \ArrayIterator($array);
        });
    }

    /**
     * @return Steps
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->steps->jsonSerialize();
    }
}
