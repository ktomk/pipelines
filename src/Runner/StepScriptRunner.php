<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\Lib;

/**
 * Class StepScriptRunner
 *
 * Running step and after-step scripts or exec scripts in a running docker container
 *
 * @package Ktomk\Pipelines\Runner
 *
 * @see StepRunner::runStepScript
 */
class StepScriptRunner
{
    /**
     * @var Runner
     */
    private $runner;

    /**
     * @var string
     */
    private $name;

    /**
     * Static factory method to create a run step script
     *
     * @param Runner $runner
     * @param string $name container name
     * @param Step $step
     *
     * @return int status of step script, non-zero if a script command failed
     */
    public static function createRunStepScript(Runner $runner, $name, Step $step)
    {
        $self = new self($runner, $name);

        return $self->runStepScript($step);
    }

    /**
     * StepScriptRunner constructor.
     *
     * @param Runner $runner
     * @param string $name of container to run script in
     */
    public function __construct(Runner $runner, $name)
    {
        $this->runner = $runner;
        $this->name = $name;
    }

    /**
     * @param Step $step
     *
     * @return int status of step script, non-zero if a script command failed
     */
    public function runStepScript(Step $step)
    {
        $streams = $this->runner->getStreams();
        $exec = $this->runner->getExec();
        $name = $this->name;

        $buffer = StepScriptWriter::writeStepScript(
            $step->getScript(),
            $this->runner->getRunOpts()->getBoolOption('script.exit-early')
        );

        $scriptRunner = $this->runner->getRunOpts()->getOption('script.runner');
        if ($this->runner->getRunOpts()->getBoolOption('script.bash-runner')) {
            $bashRunner = '/bin/bash';
            0 === $exec->capture('docker', array(
                'exec', $name, '/bin/sh', '-c', "test -f ${bashRunner} && test -x ${bashRunner}",
            )) && $scriptRunner = $bashRunner;
        }

        $status = $this->execScript($buffer, $exec, $name, $scriptRunner);
        if (0 !== $status) {
            $streams->err(sprintf("script non-zero exit status: %d\n", $status));
        }

        if (!($script = $step->getAfterScript())) {
            return $status;
        }

        $streams->out("After script:\n");

        $buffer = StepScriptWriter::writeAfterScript($script, $status);

        $afterStatus = $this->execScript($buffer, $exec, $name, $scriptRunner);
        if (0 !== $afterStatus) {
            $streams->err(sprintf("after-script non-zero exit status: %d\n", $afterStatus));
        }

        return $status;
    }

    /**
     *
     * @param string $script "\n" terminated script lines
     * @param Exec $exec
     * @param string $name
     * @param string $runner executable (absolute path), defaults to "/bin/sh"
     *
     * @return int
     */
    private function execScript($script, Exec $exec, $name, $runner = '/bin/sh')
    {
        $buffer = Lib::cmd("<<'SCRIPT' docker", array(
                'exec', '-i', $name, $runner,
            )) . "\n";
        $buffer .= $script;
        $buffer .= "SCRIPT\n";

        return $exec->pass($buffer, array());
    }
}
