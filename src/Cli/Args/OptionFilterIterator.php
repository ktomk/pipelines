<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use FilterIterator;

/**
 * Filter decorator of OptionIterator
 *
 * @see \Ktomk\Pipelines\Cli\Args\OptionIterator::getArgument
 */
class OptionFilterIterator extends FilterIterator
{
    /**
     * @var array|string[]
     */
    private $options;

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
        $buffer = implode(", ", $this->options);

        return $buffer;
    }

    /**
     * @return bool true if the current option is one of those to be filtered
     */
    public function accept()
    {
        $current = parent::current();

        return in_array($current, $this->options);
    }

    /**
     * @param array|string[] $options
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
     * @return string
     */
    private function compareOption($option)
    {
        if (!preg_match('~^[a-z0-9][a-z0-9-]*$~i', $option)) {
            throw new \InvalidArgumentException(
                sprintf("invalid option '%s'", $option)
            );
        }

        $compare = (strlen($option) === 1 ? '-' : '--')
            . $option;

        return $compare;
    }
}
