<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

/**
 * test class for a runnable
 *
 * @package Ktomk\Pipelines\Utility
 */
class RunnableTester implements Runnable
{
    /**
     * @var null|callable
     */
    private $callable;

    public function __construct($callable = null)
    {
        if (null !== $callable && !is_callable($callable)) {
            throw new \InvalidArgumentException('must be null or callable');
        }

        $this->callable = $callable;
    }

    /**
     * @param null $callbale
     *
     * @return RunnableTester
     */
    public static function create($callbale = null)
    {
        return new self($callbale);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        if ($this->callable) {
            return call_user_func($this->callable);
        }
    }
}
