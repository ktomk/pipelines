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
    private $debugPrinter;
    private $active;

    public function __construct($debugPrinter = null)
    {
        $this->debugPrinter = $debugPrinter;
        $this->active = true;
    }

    /**
     * passthru implementation
     *
     * @param string $command
     * @param array $arguments
     * @return int
     */
    public function pass($command, array $arguments)
    {
        $buffer = Lib::cmd($command, $arguments);
        $this->debug($buffer);
        if (!$this->active) {
            return 0;
        }

        $buffer === ':' ? $status = 0 : passthru($buffer, $status);
        $this->debug("exit status: $status");

        return $status;
    }

    /**
     * @param $command
     * @param array $arguments
     * @param string $out captured standard output
     * @param string $err captured standard error
     * @return int
     */
    public function capture($command, array $arguments, &$out = null, &$err = null)
    {
        $buffer = Lib::cmd($command, $arguments);
        $this->debug($buffer);
        $proc = new Proc($buffer);
        if (!$this->active) {
            return 0;
        }

        $status = $proc->run();
        $this->debug("exit status: $status");
        $out = $proc->getStandardOutput();
        $err = $proc->getStandardError();

        return $status;
    }

    private function debug($message)
    {
        if ($this->debugPrinter) {
            call_user_func($this->debugPrinter, $message);
        }
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;
    }
}
