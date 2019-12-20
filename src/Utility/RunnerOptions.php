<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Runner\RunOpts;

/**
 * aggregated args parser for RunOpts / runner options
 *
 * @package Ktomk\Pipelines\Utility\Args
 */
class RunnerOptions
{
    /**
     * @var Args
     */
    private $args;

    /**
     * @param Args $args
     * @return RunnerOptions
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
     * @throws ArgsException
     * @return RunOpts
     */
    public function run()
    {
        $runOpts = RunOpts::create();
        $this->parse($this->args, $runOpts);

        return $runOpts;
    }

    /**
     * Parse keep arguments
     *
     * @param Args $args
     * @param RunOpts $runOpts
     * @throws ArgsException
     */
    public function parse(Args $args, RunOpts $runOpts)
    {
        $runOpts->setPrefix($this->parsePrefix($args));
    }

    /**
     * @param Args $args
     * @throws ArgsException
     * @return string
     */
    private function parsePrefix(Args $args)
    {
        $prefix = $args->getOptionArgument('prefix', App::UTILITY_NAME);
        if (!preg_match('~^[a-z]{3,}$~', $prefix)) {
            ArgsException::__(sprintf("invalid prefix: '%s'", $prefix));
        }

        return $prefix;
    }
}
