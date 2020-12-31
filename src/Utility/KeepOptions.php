<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;

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
     * @var Args
     */
    private $args;

    /**
     * @param Args $args
     *
     * @return KeepOptions
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
     * @throws StatusException w/ conflicting arguments
     *
     * @return KeepOptions
     */
    public function run()
    {
        list($this->errorKeep, $this->keep) = $this->parse($this->args);

        return $this;
    }

    /**
     * Parse keep arguments
     *
     * @param Args $args
     *
     * @throws \InvalidArgumentException
     * @throws StatusException
     *
     * @return array
     */
    public function parse(Args $args)
    {
        $errorKeep = $args->hasOption('error-keep');

        $keep = $args->hasOption('keep');

        $noKeep = $args->hasOption('no-keep');

        if ($keep && $noKeep) {
            throw new StatusException('--keep and --no-keep are exclusive', 1);
        }

        if ($keep && $errorKeep) {
            throw new StatusException('--keep and --error-keep are exclusive', 1);
        }

        if ($noKeep && $errorKeep) {
            throw new StatusException('--error-keep and --no-keep are exclusive', 1);
        }

        return array($errorKeep, $keep && !$noKeep);
    }

    /**
     * @return bool
     */
    public function hasErrorKeep()
    {
        return true === $this->errorKeep;
    }
}
