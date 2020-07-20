<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;

/**
 * aggregated args parser for --no-cache argument parsing.
 *
 * @package Ktomk\Pipelines\Utility\Args
 */
class CacheOptions
{
    /**
     * @var null|bool
     */
    public $noCache;

    /**
     * @var Args
     */
    private $args;

    /**
     * @param Args $args
     *
     * @return CacheOptions
     */
    public static function bind(Args $args)
    {
        return new self($args);
    }

    /**
     * @param Args $args
     */
    public function __construct(Args $args)
    {
        $this->args = $args;
    }

    /**
     * @return CacheOptions
     */
    public function run()
    {
        list($this->noCache) = $this->parse($this->args);

        return $this;
    }

    /**
     * Parse --no-cache argument
     *
     * @param Args $args
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function parse(Args $args)
    {
        $noCache = $args->hasOption('no-cache');

        return array($noCache);
    }

    /**
     * @return bool
     */
    public function hasCache()
    {
        return false === $this->noCache;
    }
}
