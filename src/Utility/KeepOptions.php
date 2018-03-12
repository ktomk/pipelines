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
     * @var Streams
     */
    private $streams;

    /**
     * @var Args
     */
    private $args;

    /**
     * @param Args $args
     * @param Streams $streams
     */
    public function __construct(Args $args, Streams $streams)
    {
        $this->streams = $streams;
        $this->args = $args;
    }

    /**
     * @param Args $args
     * @param Streams $streams
     * @return KeepOptions
     */
    public static function bind(Args $args, Streams $streams)
    {
        return new self($args, $streams);
    }

    /**
     * @throws \InvalidArgumentException
     * @return null|int non-zero, positive integer in case of error
     *                  parsing keep option arguments
     */
    public function run()
    {
        list($status, $this->errorKeep, $this->keep, $this->noKeep)
            = $this->parse($this->args) + array(null, null, null, null);

        return $status;
    }

    /**
     * Parse keep arguments
     *
     * @param Args $args
     * @throws \InvalidArgumentException
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
            $this->error('pipelines: --keep and --no-keep are exclusive');

            return array(1);
        }
        if ($keep && $errorKeep) {
            $this->error('pipelines: --keep and --error-keep are exclusive');

            return array(1);
        }
        if ($noKeep && $errorKeep) {
            $this->error('pipelines: --error-keep and --no-keep are exclusive');

            return array(1);
        }

        return array(null, $errorKeep, $keep, $noKeep);
    }

    private function error($message)
    {
        $this->streams->err(
            sprintf("%s\n", $message)
        );
    }
}
