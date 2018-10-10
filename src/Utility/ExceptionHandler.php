<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Exception;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\ParseException;

class ExceptionHandler
{
    /**
     * @var bool
     */
    private $showStacktrace = false;

    /**
     * @var Help
     */
    private $help;
    /**
     * @var Streams
     */
    private $streams;

    /**
     * ExceptionHandler constructor.
     * @param Streams $streams
     * @param Help $help
     * @param bool $showStacktrace
     */
    public function __construct(Streams $streams, Help $help, $showStacktrace)
    {
        $this->streams = $streams;
        $this->help = $help;
        $this->showStacktrace = $showStacktrace;
    }

    /**
     * @param Runnable $runnable
     * @return mixed|void
     */
    public function handle(Runnable $runnable)
    {
        try {
            $status = $runnable->run();
        } catch (Exception $e) {
            $status = $this->handleException($e);
        }

        return $status;
    }

    /**
     * @param Exception $e
     * @return int
     */
    private function handleException(Exception $e)
    {
        $status = $e->getCode();
        $message = $e->getMessage();

        if ($this->isUnexpectedException($e)) {
            // catch unexpected exceptions for user-friendly message
            $status = 2;
            $message = sprintf('fatal: %s', $e->getMessage());
        }

        if (0 !== $status && '' !== $message) {
            $this->error(sprintf('pipelines: %s', $message));
        }

        if ($e instanceof ArgsException) {
            $this->help->showUsage();
        }

        if ($this->showStacktrace) {
            $this->debugException($e);
        }

        return $status;
    }

    private function isUnexpectedException(Exception $e)
    {
        return (
            !($e instanceof ArgsException)
            && !($e instanceof StatusException)
            && !($e instanceof ParseException)
        );
    }

    private function debugException(Exception $e)
    {
        for (; $e; $e = $e->getPrevious()) {
            $this->error('--------');
            $this->error(sprintf('class....: %s', get_class($e)));
            $this->error(sprintf('message..: %s', $e->getMessage()));
            $this->error(sprintf('code.....: %s', $e->getCode()));
            $this->error(sprintf('file.....: %s', $e->getFile()));
            $this->error(sprintf('line.....: %s', $e->getLine()));
            $this->error('backtrace:');
            $this->error($e->getTraceAsString());
        }
        $this->error('--------');
    }

    private function error($message)
    {
        $this->streams->err(
            sprintf("%s\n", $message)
        );
    }
}
