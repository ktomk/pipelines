<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value\SideEffect;

/**
 * Class DestructibleString
 *
 * Representative String w/ lifetime. E.g. representing a path to a temporary
 * directory which is getting removed on destruction.
 *
 * @package Ktomk\Pipelines
 */
class DestructibleString
{
    /**
     * @var string
     */
    private $string;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @see LibFs::rmDir
     *
     * @param string $string
     *
     * @return DestructibleString
     */
    public static function rmDir($string)
    {
        return new self(
            $string,
            'Ktomk\Pipelines\LibFs::rmDir'
        );
    }

    /**
     * @param string $string
     *
     * @return self
     */
    public static function rm($string)
    {
        return new self(
            $string,
            'Ktomk\Pipelines\LibFs::rm'
        );
    }

    /**
     * DestructibleString constructor.
     *
     * @param string $string
     * @param callable $callback
     */
    public function __construct($string, $callback)
    {
        $this->string = $string;
        $this->callback = $callback;
    }

    public function __destruct()
    {
        call_user_func($this->callback, $this->string);
    }

    public function __toString()
    {
        return $this->string;
    }
}
