<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\Lib;

/**
 * Class to abstract command execution in command line interface
 * context.
 */
class Exec
{
    /**
     * @var null|callable
     */
    private $debugPrinter;

    /**
     * @var bool
     */
    private $active = true;

    /**
     * Exec constructor.
     *
     * @param null|callable $debugPrinter
     */
    public function __construct($debugPrinter = null)
    {
        $this->debugPrinter = $debugPrinter;
    }

    /**
     * passthru implementation
     *
     * @param string $command
     * @param array $arguments
     *
     * @return int
     */
    public function pass($command, array $arguments)
    {
        $buffer = Lib::cmd($command, $arguments);
        $this->debug($buffer);
        if (!$this->active) {
            return 0;
        }

        ':' === $buffer ? $status = 0 : passthru($buffer, $status);
        $this->debug("exit status: ${status}");

        return $status;
    }

    /**
     * @param string $command
     * @param array $arguments
     * @param null|string $out captured standard output
     * @param-out string $out
     *
     * @param null|string $err captured standard error
     * @param-out string $err
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function capture($command, array $arguments, &$out = null, &$err = null)
    {
        $buffer = Lib::cmd($command, $arguments);
        $this->debug($buffer);
        if (!$this->active) {
            isset($out) || $out = '';
            isset($err) || $err = '';

            return 0;
        }

        $proc = new Proc($buffer);
        $status = $proc->run();
        $this->debug("exit status: ${status}");
        $out = $proc->getStandardOutput();
        $err = $proc->getStandardError();

        return $status;
    }

    /**
     * @param bool $active
     *
     * @return void
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function debug($message)
    {
        if ($this->debugPrinter) {
            call_user_func($this->debugPrinter, $message);
        }
    }
}
