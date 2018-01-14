<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

class Args
{
    /**
     * @var array
     */
    private $arguments;

    /**
     * @var string $utility name
     */
    private $utility;

    public static function create(array $argv)
    {
        return new self($argv);
    }

    public function __construct(array $arguments)
    {
        if (!count($arguments)) {
            throw new \InvalidArgumentException('There must be at least one argument (the command name)');
        }

        $command = array_shift($arguments);

        $this->utility = (string)$command;
        $this->arguments = $arguments;
    }

    /**
     * @param string|string[] $option
     * @return bool
     */
    public function hasOption($option)
    {
        $options = (array)$option;
        $consume = array();

        foreach ($options as $option) {
            $compare = $this->compareOption($option);

            foreach ($this->arguments as $index => $argument) {
                if ($argument === '--') {
                    break;
                }
                if ($argument === $compare) {
                    $consume[] = $index;
                }
            }
        }

        # consume arguments
        if ($consume) {
            foreach ($consume as $index) {
                unset($this->arguments[$index]);
            }
        }

        return (bool)$consume;
    }

    /**
     * @return string|null
     */
    public function getFirstRemainingOption()
    {
        foreach ($this->arguments as $argument) {
            if (strlen($argument) < 2) {
                continue;
            }
            if ($argument === '--') {
                break;
            }
            if ($argument[0] === '-') {
                return $argument;
            }
        }

        return null;
    }

    /**
     * turn an option into a compare string (long-switch if applicable)
     *
     * verifies to the need of the args parser, throws an exception if
     * non-fitting.
     *
     * @param $option
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

    /**
     * @param $option
     * @param string|bool|null $default [optional]
     * @param bool $required [optional]
     * @return string|null
     * @throws ArgsException
     */
    public function getOptionArgument($option, $default = null, $required = false)
    {
        $compare = $this->compareOption($option);

        $result = null;
        $consume = array();
        foreach ($this->arguments as $index => $argument)
        {
            if ($argument === '--') {
                break;
            }
            if ($argument !== $compare) {
                continue;
            }
            if (!isset($this->arguments[$index + 1]) || $this->arguments[$index + 1] === '--') {
                ArgsException::__(
                    sprintf("error: option '%s' requires an argument", $option)
                );
            }

            $result = $this->arguments[$index + 1];
            $consume = array($index, $index + 1);
        }

        # consume arguments
        if ($consume) {
            foreach ($consume as $index) {
                unset($this->arguments[$index]);
            }
        }

        if ($result === null) {
            if ($required) {
                ArgsException::__(
                    sprintf("error: option '%s' is not optional", $option)
                );
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
