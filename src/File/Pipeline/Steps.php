<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\ParseException;
use Ktomk\Pipelines\File\Pipeline;

/**
 * Class Steps
 *
 * A Pipeline consist of Steps. Some of them can be parallel.
 */
class Steps implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var Pipeline
     */
    private $pipeline;

    /**
     * @var array pipeline definition
     */
    private $array;

    /**
     * @var array|Step[] steps of the pipeline
     * @see parseSteps
     */
    private $steps;

    /**
     * @var callable
     */
    private $getIteratorFunctor;

    /**
     * Pipeline constructor.
     *
     * @param Pipeline $pipeline
     * @param array $definition
     *
     * @throws ParseException
     */
    public function __construct(Pipeline $pipeline, array $definition)
    {
        // quick validation
        if (!isset($definition[0])) {
            ParseException::__('Steps requires a tree of steps');
        }

        $this->pipeline = $pipeline;
        $this->parseSteps($definition);
    }

    /**
     * @return Pipeline
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }

    /**
     * @return array|Step[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param $functor
     *
     * @see getIterator
     */
    public function setGetIteratorFunctor($functor)
    {
        $this->getIteratorFunctor = $functor;
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
        foreach ($this->getSteps() as $step) {
            $steps[] = $step->jsonSerialize();
        }

        return array(
            'steps' => $steps,
        );
    }

    /* @see \ArrayAccess */

    public function offsetExists($offset)
    {
        return isset($this->steps[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return Step
     */
    public function offsetGet($offset)
    {
        return $this->steps[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Steps offsets are read-only');
    }

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Steps offsets are read-only');
    }

    /* @see \Countable */

    public function count()
    {
        return count($this->steps);
    }

    /* @see \IteratorAggregate */

    /**
     * @return \ArrayIterator|Step[]
     */
    public function getIterator()
    {
        return is_callable($this->getIteratorFunctor)
            ? call_user_func($this->getIteratorFunctor, $this)
            : new \ArrayIterator($this->steps);
    }

    private function parseSteps(array $definition)
    {
        $this->array = array();
        $this->steps = array();

        foreach ($definition as $node) {
            if (!is_array($node)) {
                ParseException::__(sprintf('Step expected array, got %s', gettype($node)));
            }
            if (empty($node)) {
                ParseException::__('Step expected, got empty array');
            }

            $keys = array_keys($node);
            $name = $keys[0];
            if (!in_array($name, array('step', 'parallel'), true)) {
                ParseException::__(sprintf('Unknown pipeline step "%s", expected "step" or "parallel"', $name));
            }

            $this->node($node, $name);
        }
    }

    /**
     * @param int $index of step, from the zero based index in the list of steps
     * @param array $step
     *
     * @return Step
     */
    private function step($index, array $step)
    {
        return new Step($this->pipeline, $index, $step);
    }

    /**
     * @param array $node
     * @param $name
     */
    private function node(array $node, $name)
    {
        $this->array[] = $node;
        switch ($name) {
            case 'step':
                $this->steps[] = $this->step(count($this->steps), $node[$name]);

                break;
            case 'parallel':
                $this->parallel($node[$name]);

                break;
            default:
                // @codeCoverageIgnoreStart
                throw new \BadMethodCallException(
                    sprintf('Unchecked name condition: "%s"', $name)
                );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param $node
     */
    private function parallel(array $node)
    {
        $group = array();
        foreach ($node as $step) {
            if (!(isset($step['step']) && is_array($step['step']))) {
                ParseException::__('Parallel step must consist of steps only');
            }
            $group[] = $step['step'];
        }

        foreach ($group as $index => $step) {
            $this->steps[] = $this->step(
                count($this->steps),
                $step
            );
        }
    }
}
