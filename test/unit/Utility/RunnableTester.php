<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;

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

    /**
     * @param null $callable
     *
     * @return RunnableTester
     */
    public static function create($callable = null)
    {
        return new self($callable);
    }

    public function __construct($callable = null)
    {
        if (null !== $callable && !is_callable($callable)) {
            throw new InvalidArgumentException('must be null or callable');
        }

        $this->callable = $callable;
    }

    /**
     * @return mixed
     */
    public function run()
    {
        if ($this->callable) {
            return call_user_func($this->callable);
        }

        return null;
    }
}
