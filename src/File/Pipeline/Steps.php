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
    private $steps = array();

    /**
     * @var callable
     */
    private $getIteratorFunctor;

    /**
     * Get full iteration of steps' steps
     *
     * Gracefully handles null for steps.
     *
     * @param $steps
     *
     * @return StepsIterator
     */
    public static function fullIter($steps)
    {
        if (null === $steps) {
            return new StepsIterator(new \ArrayIterator(array()));
        }

        if (!$steps instanceof Steps) {
            throw new \InvalidArgumentException('Invalid steps argument');
        }

        $iter = $steps->getIterator();
        $iter->setNoManual(true);

        return $iter;
    }

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
     * @return StepsIterator
     */
    public function getIterator()
    {
        if (is_callable($this->getIteratorFunctor)) {
            return new StepsIterator(
                call_user_func($this->getIteratorFunctor, $this)
            );
        }

        return new StepsIterator(
            new \ArrayIterator($this->steps)
        );
    }

    private function parseSteps(array $definition)
    {
        $this->array = array();
        $this->steps = array();

        foreach ($definition as $node) {
            if (!is_array($node)) {
                ParseException::__(sprintf('Step expected, got %s', gettype($node)));
            }
            if (empty($node)) {
                ParseException::__('Step expected, got empty array');
            }

            $keys = array_keys($node);
            $name = $keys[0];
            if (!in_array($name, array('step', 'parallel'), true)) {
                ParseException::__(sprintf("Unexpected pipeline property '%s', expected 'step' or 'parallel'", $name));
            }

            $this->parseNode($node, $name);
        }
    }

    /**
     * @param int $index of step, from the zero based index in the list of steps
     * @param array $step
     * @param array $env [optional] environment variables in array notation for the new step
     *
     * @return Step
     */
    private function parseStep($index, array $step, array $env = array())
    {
        if (0 === $index && isset($step['trigger']) && 'manual' === $step['trigger']) {
            ParseException::__("The first step of a pipeline can't be manually triggered");
        }

        return new Step($this->pipeline, $index, $step, $env);
    }

    /**
     * @param array $node
     * @param $name
     */
    private function parseNode(array $node, $name)
    {
        $this->array[] = $node;
        switch ($name) {
            case 'step':
                $this->steps[] = $this->parseStep(count($this->steps), $node[$name]);

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

        $total = count($group);
        foreach ($group as $index => $step) {
            $this->steps[] = $this->parseStep(
                count($this->steps),
                $step,
                array(
                    'BITBUCKET_PARALLEL_STEP' => $index,
                    'BITBUCKET_PARALLEL_STEP_COUNT' => $total,
                )
            );
        }
    }
}
