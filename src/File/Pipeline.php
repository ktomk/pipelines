<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\File\Dom\FileNode;
use Ktomk\Pipelines\File\Pipeline\Steps;
use Ktomk\Pipelines\Value\StepExpression;

class Pipeline implements FileNode
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
        $this->parsePipeline($definition);
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * get id of pipeline within the corresponding pipelines object
     *
     * @return string pipeline-id
     */
    public function getId()
    {
        $id = $this->file->getPipelines()->getId($this);
        if (null === $id) {
            // @codeCoverageIgnoreStart
            throw new \BadMethodCallException('non associated pipeline has no id');
            // @codeCoverageIgnoreEnd
        }

        return $id;
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

    /**
     * @return void
     */
    private function parsePipeline(array $array)
    {
        $this->steps = new Steps(
            $this,
            $this->filterMapList($array, array('step', 'parallel'))
        );
    }

    /**
     * filter a list (in indexed array) af maps (string indexed array)
     * with a single entry only
     *
     * @param array|array[] $mapList
     * @param array|string[] $keys
     *
     * @return array
     */
    private function filterMapList(array $mapList, array $keys)
    {
        $result = array();
        foreach ($mapList as $map) {
            if (!is_array($map) || 0 === count($map)) {
                continue;
            }
            $key = key($map);
            if (!in_array($key, $keys, true)) {
                continue;
            }
            $value = current($map);
            $result[] = array($key => $value);
        }

        return $result;
    }
}
