<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\ParseException;
use Ktomk\Pipelines\File\Pipeline;

/**
 * Class Steps
 *
 * A Pipeline consist of Steps. Some of them can be parallel.
 *
 * @template-implements \IteratorAggregate<int, Step>
 *
 * @see Steps::getIterator
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
     *
     * @see parseSteps
     */
    private $steps = array();

    /**
     * @var null|callable
     */
    private $getIteratorFunctor;

    /**
     * Get full iteration of steps' steps
     *
     * Gracefully handles null for steps.
     *
     * @param null|Steps $steps
     *
     * @return StepsIterator
     */
    public static function fullIter(Steps $steps = null)
    {
        if (null === $steps) {
            return new StepsIterator(new \ArrayIterator(array()));
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
            throw new ParseException('Steps requires a tree of steps');
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
     * @see getIterator
     *
     * @param null|callable $functor
     *
     * @return void
     */
    public function setGetIteratorFunctor($functor)
    {
        $this->getIteratorFunctor = $functor;
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
        $steps = array();
        foreach ($this->getSteps() as $step) {
            $steps[] = $step->jsonSerialize();
        }

        return array(
            'steps' => $steps,
        );
    }

    /* @see \ArrayAccess */

    #[\ReturnTypeWillChange]
    /**
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->steps[$offset]);
    }

    #[\ReturnTypeWillChange]
    /**
     * @param mixed $offset
     *
     * @return Step
     */
    public function offsetGet($offset)
    {
        return $this->steps[$offset];
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Steps offsets are read-only');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Steps offsets are read-only');
    }

    /* @see \Countable */

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->steps);
    }

    /* @see \IteratorAggregate */

    #[\ReturnTypeWillChange]
    /**
     * @return StepsIterator
     */
    public function getIterator()
    {
        $iteratorFunctor = $this->getIteratorFunctor;
        if (isset($iteratorFunctor)) {
            $iterator = call_user_func($iteratorFunctor, $this);
        } else {
            $iterator = new \ArrayIterator($this->steps);
        }

        return new StepsIterator($iterator);
    }

    /**
     * @param array $definition
     *
     * @return void
     */
    private function parseSteps(array $definition)
    {
        $this->array = array();
        $this->steps = array();

        foreach ($definition as $node) {
            if (!is_array($node)) {
                throw new ParseException(sprintf('Step expected, got %s', gettype($node)));
            }
            if (empty($node)) {
                throw new ParseException('Step expected, got empty array');
            }

            $keys = array_keys($node);
            $name = $keys[0];
            if (!in_array($name, array('step', 'parallel'), true)) {
                throw new ParseException(
                    sprintf("Unexpected pipeline property '%s', expected 'step' or 'parallel'", $name)
                );
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
            throw new ParseException("The first step of a pipeline can't be manually triggered");
        }

        return new Step($this->pipeline, $index, $step, $env);
    }

    /**
     * @param array $node
     * @param string $name
     *
     * @return void
     */
    private function parseNode(array $node, $name)
    {
        $this->array[] = $node;
        switch ($name) {
            case 'step':
                if (empty($node[$name])) {
                    throw new ParseException('step requires a script');
                }
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
     *
     * @return void
     */
    private function parallel(array $node)
    {
        $group = array();
        foreach ($node as $step) {
            if (!(isset($step['step']) && is_array($step['step']))) {
                throw new ParseException('Parallel step must consist of steps only');
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
