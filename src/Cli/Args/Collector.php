<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

/**
 * Class Collector
 *
 * Collect arguments and their values from on Args to a new one.
 * consuming them.
 *
 * @package Ktomk\Pipelines\Cli\Args
 */
class Collector extends Args
{
    /**
     * @var Args
     */
    private $args;

    public function __construct(Args $args)
    {
        $this->args = $args;
    }

    /**
     * @param string|string[] $option one or more options to collect
     *
     * @throws \InvalidArgumentException
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     *
     * @return void
     */
    public function collect($option)
    {
        /** @var OptionFilterIterator|OptionIterator $options */
        $options = new OptionFilterIterator($this->args, $option);
        $consume = array();
        foreach ($options as $index => $value) {
            $this->arguments[] = $value;
            $consume[] = $index;
            $this->arguments[] = $options->getArgument();
            $consume[] = $index + 1;
        }

        foreach ($consume as $index) {
            unset($this->args->arguments[$index]);
        }
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->arguments;
    }
}
