<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;

/**
 * aggregated args parser for --error-keep, --keep and --no-keep argument
 * parsing.
 *
 * @package Ktomk\Pipelines\Utility\Args
 */
class KeepOptions
{
    /**
     * @var null|bool
     */
    public $errorKeep;

    /**
     * @var null|bool
     */
    public $keep;

    /**
     * @var null|bool
     */
    public $noKeep;

    /**
     * @var Args
     */
    private $args;

    /**
     * @param Args $args
     */
    public function __construct(Args $args)
    {
        $this->args = $args;
    }

    /**
     * @param Args $args
     * @param Streams $streams
     * @return KeepOptions
     */
    public static function bind(Args $args)
    {
        return new self($args);
    }

    /**
     * @throws StatusException w/ conflicting arguments
     * @return KeepOptions
     */
    public function run()
    {
        list($this->errorKeep, $this->keep, $this->noKeep)
            = $this->parse($this->args);

        return $this;
    }

    /**
     * Parse keep arguments
     *
     * @param Args $args
     * @throws \InvalidArgumentException
     * @throws StatusException
     * @return array|int
     */
    public function parse(Args $args)
    {
        /** @var bool $errorKeep keep on errors */
        $errorKeep = $args->hasOption('error-keep');

        /** @var bool $keep containers */
        $keep = $args->hasOption('keep');

        /** @var bool $noKeep do not keep on errors */
        $noKeep = $args->hasOption('no-keep');

        if ($keep && $noKeep) {
            StatusException::status(1, '--keep and --no-keep are exclusive');
        }

        if ($keep && $errorKeep) {
            StatusException::status(1, '--keep and --error-keep are exclusive');
        }

        if ($noKeep && $errorKeep) {
            StatusException::status(1, '--error-keep and --no-keep are exclusive');
        }

        return array($errorKeep, $keep, $noKeep);
    }
}
