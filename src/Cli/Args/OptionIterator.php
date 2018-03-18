<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use IteratorIterator;
use Ktomk\Pipelines\Cli\ArgsException;

class OptionIterator extends IteratorIterator
{
    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * Iterator constructor.
     * @param Args $args
     */
    public function __construct(Args $args)
    {
        $this->iterator = new Iterator($args);
        parent::__construct($this->iterator);
        $this->rewind();
    }

    public function hasArgument()
    {
        $next = $this->iterator->getNext();

        if (null === $next) {
            return false;
        }

        if ('--' === $next) {
            return false;
        }

        return true;
    }

    /**
     * @throws ArgsException
     */
    public function getArgument()
    {
        if (!$this->hasArgument()) {
            ArgsException::__(
                sprintf("option %s requires an argument", $this->current())
            );
        }

        return $this->iterator->getNext();
    }

    /* Iterator */

    public function current()
    {
        return $this->iterator->current();
    }

    /**
     * Move forward to next option
     */
    public function next()
    {
        parent::next();
        $this->forwardToOption();
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        if (!$this->iterator->valid()) {
            return false;
        }

        $current = parent::current();

        return  !('--' === $current);
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        parent::rewind();
        $this->forwardToOption();
    }

    /**
     * Forward the iterator to the current option
     */
    private function forwardToOption()
    {
        while (
            parent::valid()
            && (null !== $current = parent::current())
            && (
                (strlen($current) < 2)
                || (0 !== strpos($current, '-'))
            )
        ) {
            parent::next();
        }
    }
}
