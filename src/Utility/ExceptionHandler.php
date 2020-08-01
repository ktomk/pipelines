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
    private $showStacktraceFlag;

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
     *
     * @param Streams $streams
     * @param Help $help
     * @param bool $showStacktrace
     */
    public function __construct(Streams $streams, Help $help, $showStacktrace)
    {
        $this->streams = $streams;
        $this->help = $help;
        $this->showStacktraceFlag = $showStacktrace;
    }

    /**
     * @param Runnable $runnable
     *
     * @return null|mixed
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
     * @param StatusRunnable $runnable
     *
     * @return int
     */
    public function handleStatus(StatusRunnable $runnable)
    {
        return (int)$this->handle($runnable);
    }

    /**
     * @param Exception $e
     *
     * @return int
     */
    private function handleException(Exception $e)
    {
        $status = $e->getCode();
        $message = $e->getMessage();

        // "catch" unexpected exceptions for user-friendly message
        if (!is_int($status) || $this->isUnexpectedException($e)) {
            $status = 2;
            $message = sprintf('fatal: %s', $e->getMessage());
        }

        $this->showError($status, $message);
        $this->showUsage($e);
        $this->showStacktrace($e);

        return $status;
    }

    /**
     * Show error message
     *
     * @param int $status
     * @param string $message
     *
     * @return void
     */
    private function showError($status, $message)
    {
        if (0 !== $status && '' !== $message) {
            $this->error(sprintf('pipelines: %s', $message));
        }
    }

    /**
     * Some exceptions can show usage
     *
     * @param Exception $e
     *
     * @return void
     */
    private function showUsage(Exception $e)
    {
        if ($e instanceof ArgsException) {
            $this->help->showUsage();
        }
    }

    /**
     * Show a stacktrace for debugging purposes (`--debug`` flag)
     *
     * @param Exception $e
     *
     * @return void
     */
    private function showStacktrace(Exception $e)
    {
        if ($this->showStacktraceFlag) {
            $this->debugException($e);
        }
    }

    /**
     * Some exceptions are not unexpected
     *
     * The pipelines utility uses *some* exceptions to signal program
     * flow in context of the command line utility. E.g. to not call
     * exit() somewhere deep in the code (StatusException), lazy command
     * line argument handling (ArgsException) and lazy pipelines.yml
     * file parsing (ParseException).
     *
     * @param Exception $e
     *
     * @return bool
     */
    private function isUnexpectedException(Exception $e)
    {
        return (
            !($e instanceof ArgsException)
            && !($e instanceof StatusException)
            && !($e instanceof ParseException)
        );
    }

    /**
     * @param Exception $e
     *
     * @return void
     */
    private function debugException(Exception $e)
    {
        $this->debugInfoOfPipelinesItself();

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

    private function debugInfoOfPipelinesItself()
    {
        $this->error(sprintf(
            'pipelines: version %s w/ php %s (libyaml: %s)',
            Version::resolve(App::VERSION),
            PHP_VERSION,
            \phpversion('yaml') ?: 'n/a'
        ));
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function error($message)
    {
        $this->streams->err(
            sprintf("%s\n", $message)
        );
    }
}
