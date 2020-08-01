<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

/**
 * Class OptionMatcher
 *
 * Match one or multiple short/long opts
 *
 * @package Ktomk\Pipelines\Cli\Args
 */
class OptionMatcher
{
    /**
     * @var string[]
     */
    private $option;

    /**
     * @var bool
     */
    private $equals;

    /**
     * @param string $option
     * @param string $arg
     * @param bool $equals
     *
     * @return bool
     */
    public static function matchOptionArg($option, $arg, $equals)
    {
        $optLen = strlen($option);
        if (0 === $optLen) {
            return false;
        }

        $argLen = strlen($arg);
        if ($argLen < 2) {
            return false;
        }

        if ('--' === $arg) {
            return false;
        }

        if ('-' !== $arg[0]) {
            return false;
        }

        if ((1 !== $optLen) !== ('-' === $arg[1])) {
            return false;
        }

        $buffer = substr($arg, 1 === $optLen ? 1 : 2);

        if ($equals && false !== $posEquals = strpos($buffer, '=')) {
            if (0 === $posEquals) {
                return false;
            }
            $buffer = substr($buffer, 0, $posEquals);
        }

        if (1 === $optLen) {
            return false !== strpos($buffer, $option);
        }

        return $buffer === $option;
    }

    /**
     * @param string|string[] $option
     *
     * @return OptionMatcher
     */
    public static function create($option)
    {
        return new self($option, true);
    }

    /**
     * OptionMatcher constructor.
     *
     * @param string|string[] $option
     * @param bool $equals support equals-sign while matching
     */
    public function __construct($option, $equals)
    {
        $this->option = (array)$option;
        $this->equals = $equals;
    }

    /**
     * @param string $arg
     *
     * @return bool
     */
    public function match($arg)
    {
        foreach ($this->option as $option) {
            if (self::matchOptionArg($option, $arg, $this->equals)) {
                return true;
            }
        }

        return false;
    }
}
