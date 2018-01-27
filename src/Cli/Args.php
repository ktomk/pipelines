<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\Cli\Args\Args as ArgsArgs;
use Ktomk\Pipelines\Cli\Args\OptionFilterIterator;
use Ktomk\Pipelines\Cli\Args\OptionIterator;

/**
 * Class Args
 *
 * Facade class to access arguments related functionality
 *
 * @package Ktomk\Pipelines\Cli
 */
class Args extends ArgsArgs
{
    /**
     * @var string $utility name
     */
    private $utility;

    /**
     * create from $argv
     *
     * @param array $argv
     * @return Args
     */
    public static function create(array $argv)
    {
        if (0 === count($argv)) {
            throw new \InvalidArgumentException('There must be at least one argument (the command name)');
        }

        $command = (string)array_shift($argv);
        $args = new self($argv);
        $args->utility = $command;

        return $args;
    }

    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * test for option and consume any of them. these options
     * are all w/o option argument. e.g. check for -v/--verbose.
     *
     * @param string|string[] $option
     * @return bool
     */
    public function hasOption($option)
    {
        $options = new OptionFilterIterator($this, $option);

        # consume arguments
        foreach ($options as $index => $argument) {
            unset($this->arguments[$index]);
        }

        return isset($index);
    }

    /**
     * @return string|null
     */
    public function getFirstRemainingOption()
    {
        foreach (new OptionIterator($this) as $option) {
            return $option;
        }

        return null;
    }

    /**
     * Get the argument of an option.
     *
     * NOTE: returns only the first option value if multiple options would match
     *
     * @param string|string[] $option
     * @param string|bool|null $default [optional]
     * @param bool $required [optional]
     * @return string|bool|null
     * @throws ArgsException
     */
    public function getOptionArgument($option, $default = null, $required = false)
    {

        $result = null;

        /** @var OptionFilterIterator|OptionIterator $options */
        $options = new OptionFilterIterator($this, $option);
        foreach ($options as $index => $argument) {
            /** @scrutinizer ignore-call */
            $result = $options->getArgument();
            unset($this->arguments[$index]);
            unset($this->arguments[$index + 1]);
            break; # first match
        }

        if ($result === null) {
            if ($required) {
                ArgsException::__(sprintf(
                    "error: option %s is not optional",
                    $options->/** @scrutinizer ignore-call */
                        getOptionDescription()
                ));
            }

            $result = $default;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getUtility()
    {
        return $this->utility;
    }

    /**
     * @return array
     */
    public function getRemaining()
    {
        return $this->arguments;
    }
}
