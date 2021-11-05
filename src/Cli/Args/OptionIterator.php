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
     *
     * @param Args $args
     */
    public function __construct(Args $args)
    {
        $this->iterator = new Iterator($args);
        parent::__construct($this->iterator);
        $this->rewind();
    }

    /**
     * @return bool
     */
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
     *
     * @return string
     */
    public function getArgument()
    {
        if (!$this->hasArgument()) {
            throw new ArgsException(
                sprintf('option %s requires an argument', (string)$this->current())
            );
        }

        return (string)$this->iterator->getNext();
    }

    /* Iterator */

    #[\ReturnTypeWillChange]
    /**
     * @return string
     */
    public function current()
    {
        $current = $this->iterator->current();
        if (null === $current) {
            throw new \BadMethodCallException('Invalid iterator state for current()');
        }

        return $current;
    }

    #[\ReturnTypeWillChange]
    /**
     * Move forward to next option
     */
    public function next()
    {
        parent::next();
        $this->forwardToOption();
    }

    #[\ReturnTypeWillChange]
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

        return !('--' === $current);
    }

    #[\ReturnTypeWillChange]
    /**
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        parent::rewind();
        $this->forwardToOption();
    }

    /* seeks */

    /**
     * seek option
     *
     * seek single option (technically only long options are supported)
     * which allows --long-option[=argument] optional arguments.
     *
     * @param string|string[] $option
     *
     * @return void
     */
    public function seekOption($option)
    {
        for (
            $this->forwardToOption();
            $this->valid() && !$this->currentMatchesOption($option);
            $this->forwardToOption()
        ) {
            parent::next();
        }
    }

    /**
     * @param string|string[] $option
     *
     * @return bool
     */
    public function currentMatchesOption($option)
    {
        return $this->valid() ? OptionMatcher::create($option)->match($this->current()) : false;
    }

    /**
     * Forward the iterator to the current option
     *
     * @return void
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
