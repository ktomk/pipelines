<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use FilterIterator;
use InvalidArgumentException;

/**
 * Filter decorator of OptionIterator
 *
 * @method string getArgument()
 *
 * @see \Ktomk\Pipelines\Cli\Args\OptionIterator::getArgument
 */
class OptionFilterIterator extends FilterIterator
{
    /**
     * @var array|string[]  ['--env', '-e']
     */
    private $options;

    /**
     * OptionFilterIterator constructor.
     *
     * @param Args $args
     * @param string|string[] $options ['env', 'e']
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Args $args, $options)
    {
        $this->initOptions((array)$options);
        $iterator = new OptionIterator($args);
        parent::__construct($iterator);
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        $arguments = array();

        for ($this->rewind(); $this->valid(); $this->next()) {
            /** @scrutinizer ignore-call */
            $arguments[] = $this->getArgument();
        }

        return $arguments;
    }

    /**
     * get option descriptor
     *
     * @return string
     */
    public function getOptionDescription()
    {
        return implode(', ', $this->options);
    }

    #[\ReturnTypeWillChange]
    /**
     * @return bool true if the current option is one of those to be filtered
     */
    public function accept()
    {
        $current = $this->current();

        return in_array($current, $this->options, true);
    }

    /**
     * @param array|string[] $options
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private function initOptions(array $options)
    {
        $build = array();
        foreach ($options as $option) {
            $compare = $this->compareOption($option);
            $build[] = $compare;
        }

        $this->options = $build;
    }

    /**
     * turn an option into a compare string (long-switch if applicable)
     *
     * verifies to the need of the args parser, throws an exception if
     * non-fitting.
     *
     * @param string $option
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    private function compareOption($option)
    {
        $buffer = (1 === strlen($option) ? '-' : '--') . $option;

        if (!preg_match('~^[a-z0-9][a-z0-9-]*$~i', $option)) {
            throw new InvalidArgumentException(
                sprintf('invalid option %s', $buffer)
            );
        }

        return $buffer;
    }
}
