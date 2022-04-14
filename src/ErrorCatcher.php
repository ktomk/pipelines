<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * catch errors w/ restore
 *
 * @package Ktomk\Pipelines
 */
class ErrorCatcher
{
    /**
     * @var null|array
     */
    private $last;

    /**
     * @var null|array
     */
    private $previous;

    /**
     * @var int
     */
    private $level;

    /**
     * @return ErrorCatcher
     */
    public static function create()
    {
        return new self();
    }

    public function __construct()
    {
        $this->start();
    }

    /**
     * @return bool an error occurred between start and end
     */
    public function end()
    {
        $this->last = error_get_last();
        $error = $this->last !== $this->previous;
        error_reporting($this->level);

        return $error;
    }

    /**
     * @return null|string
     */
    public function getLastErrorMessage()
    {
        $error = $this->last !== $this->previous;

        return $error ? $this->last['message'] : null;
    }

    /**
     * start catching session (is auto-started on create)
     *
     * @return void
     */
    private function start()
    {
        @$void;
        $this->previous = error_get_last();
        $this->level = error_reporting();
        error_reporting(0);
    }
}
