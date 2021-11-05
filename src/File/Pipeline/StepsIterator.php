<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

/**
 * Class StepsIterator
 *
 * @package Ktomk\Pipelines\File\Pipeline
 *
 * @template-implements \Iterator<int, Step>
 */
class StepsIterator implements \Iterator
{
    /**
     * @var \Iterator
     */
    private $inner;

    /**
     * @var null|int
     */
    private $index;

    /**
     * @var null|Step
     */
    private $current;

    /**
     * @var bool override trigger: manual in iteration
     */
    private $noManual = false;

    /**
     * StepsIterator constructor.
     *
     * @param \Iterator $iterator
     */
    public function __construct(\Iterator $iterator)
    {
        $this->inner = $iterator;
    }

    /**
     * @return null|int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Index of the pipeline step being iterated
     *
     * Undefined behaviour if the iteration has not yet been
     * started (e.g. the iterator has not yet been rewound)
     *
     * @return null|int
     */
    public function getStepIndex()
    {
        return isset($this->current) ? $this->current->getIndex() : null;
    }

    /**
     * Iteration might stop at a manual step. If
     * that is the case, isManual() will be true
     * *after* the iteration.
     *
     * @return bool
     */
    public function isManual()
    {
        return 0 !== $this->index
            && !$this->noManual
            && $this->current()
            && isset($this->current)
            && $this->current->isManual();
    }

    /** @see \Iterator * */

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->index++;
        $this->inner->next();
    }

    #[\ReturnTypeWillChange]
    /**
     * @return int
     */
    public function key()
    {
        return $this->inner->key();
    }

    #[\ReturnTypeWillChange]
    /**
     * @return Step
     */
    public function current()
    {
        return $this->current = $this->inner->current();
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        if ($this->isManual()) {
            return false;
        }

        return $this->inner->valid();
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->index = 0;
        $this->inner->rewind();
    }

    /**
     * @param bool $noManual
     *
     * @return void
     */
    public function setNoManual($noManual)
    {
        $this->noManual = (bool)$noManual;
    }
}
