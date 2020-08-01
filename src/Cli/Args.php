<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use InvalidArgumentException;
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
    private $utility = '';

    /**
     * create from $argv
     *
     * @param array $argv
     *
     * @throws InvalidArgumentException
     *
     * @return Args
     */
    public static function create(array $argv)
    {
        if (0 === count($argv)) {
            throw new InvalidArgumentException('There must be at least one argument (the command name)');
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
     *
     * @throws InvalidArgumentException
     *
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
     * map options on array keyed with options to parameters
     *
     * both provided to callback, options as first, parameter as
     * second parameter.
     *
     * @param array $map
     * @param callable $callback
     *
     * @return array results
     */
    public function mapOption(array $map, $callback)
    {
        $results = array();
        foreach ($map as $option => $parameter) {
            $result = array(false, null);
            if ($this->hasOption($option)) {
                $result = array(true, call_user_func($callback, $option, $parameter));
            }
            $results[$option] = $result;
        }

        return $results;
    }

    /**
     * @return null|string
     */
    public function getFirstRemainingOption()
    {
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach (new OptionIterator($this) as $option) {
            return $option;
        }

        return null;
    }

    /**
     * get option w/ optional argument
     *
     * optional by equal sign (--option=argument)
     *
     * @param string|string[] $option
     * @param null|bool|string $default [optional] argument
     *
     * @return null|bool|string null when not found, default when found with no argument or the argument
     */
    public function getOptionOptionalArgument($option, $default = null)
    {
        $result = null;

        $options = new OptionIterator($this);
        $options->seekOption($option);
        if (!$options->currentMatchesOption($option)) {
            return $result;
        }

        $buffer = $options->current();
        $equalPos = strpos($buffer, '=');
        if (false === $equalPos) {
            $result = $default;
        } else {
            $result = (string)substr($buffer, $equalPos + 1);
        }
        unset($this->arguments[$options->key()]);

        return $result;
    }

    /**
     * Get the argument of an option.
     *
     * NOTE: returns only the first option value if multiple options would match
     *
     * @param string|string[] $option
     * @param null|int|string $default [optional]
     * @param bool $required [optional]
     *
     * @throws InvalidArgumentException
     * @throws ArgsException
     *
     * @return null|bool|string
     *
     * @psalm-template T as null|string|int
     * @psalm-param T $default
     * @psalm-return (T is null ? null|string : (T is string ? string : null|int))
     */
    public function getOptionArgument($option, $default = null, $required = false)
    {
        $result = null;

        $options = new OptionFilterIterator($this, $option);
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($options as $index => $argument) {
            /** @scrutinizer ignore-call */
            $result = $options->getArgument();
            unset($this->arguments[$index], $this->arguments[$index + 1]);

            break; # first match
        }

        if (null === $result) {
            if ($required) {
                throw new ArgsException(sprintf(
                    'option %s is not optional',
                    $options->getOptionDescription()
                ));
            }

            $result = $default;
        }

        return $result;
    }

    /**
     * Get the argument of an option.
     *
     * NOTE: returns only the first option value if multiple options would match
     *
     * @param string|string[] $option
     * @param string $default
     * @param bool $required [optional]
     *
     * @throws InvalidArgumentException
     * @throws ArgsException
     *
     * @return string
     */
    public function getStringOptionArgument($option, $default, $required = false)
    {
        if (!is_string($default)) {
            throw new InvalidArgumentException(sprintf('default value must be string, %s given', gettype($default)));
        }

        return (string)$this->getOptionArgument($option, $default, $required);
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
