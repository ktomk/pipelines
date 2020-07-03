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

        $buffer = "# this /bin/sh script is generated from a pipeline script\n";
        $buffer .= "set -e\n";
        $buffer .= $this->generateScript(
            $step->getScript(),
            $this->getAfterCommand($this->runner->getRunOpts()->getBoolOption('script.exit-early'))
        );

        $status = $this->execScript($buffer, $exec, $name);
        if (0 !== $status) {
            $streams->err(sprintf("script non-zero exit status: %d\n", $status));
        }

        if (!($script = $step->getAfterScript())) {
            return $status;
        }

        $streams->out("After script:\n");

        $buffer = "#  this /bin/sh script is generated from a pipeline after-script\n";
        $buffer .= "set -e\n";
        $buffer .= sprintf("BITBUCKET_EXIT_CODE=%d\n", $status);
        $buffer .= "export BITBUCKET_EXIT_CODE\n";
        $buffer .= $this->generateScript($script);

        $afterStatus = $this->execScript($buffer, $exec, $name);
        if (0 !== $afterStatus) {
            $streams->err(sprintf("after-script non-zero exit status: %d\n", $afterStatus));
        }

        return $status;
    }

    /**
     * after command
     *
     * optional command after each line in a step script to more strictly
     * check the last step script command exit status.
     *
     * for debugging purposes.
     *
     * @param bool $strict
     *
     * @return null|string
     */
    private function getAfterCommand($strict)
    {
        return $strict ? '( r=$?; if [ $r -ne 0 ]; then exit $r; fi; ) || exit' . "\n" : null;
    }

    /**
     *
     * @param string $script "\n" terminated script lines
     * @param Exec $exec
     * @param string $name
     *
     * @return int
     */
    private function execScript($script, Exec $exec, $name)
    {
        $buffer = Lib::cmd("<<'SCRIPT' docker", array(
                'exec', '-i', $name, '/bin/sh',
            )) . "\n";
        $buffer .= $script;
        $buffer .= "SCRIPT\n";

        return $exec->pass($buffer, array());
    }

    /**
     * @param array|string[] $script
     * @param null|string $afterCommand
     *
     * @return string
     */
    private function generateScript(array $script, $afterCommand = null)
    {
        $buffer = '';
        foreach ($script as $index => $line) {
            $command = $this->generateCommand($line);
            $line && $buffer .= 'printf \'\\n\'' . "\n";
            $buffer .= 'printf \'\\035+ %s\\n\' ' . Lib::quoteArg($command) . "\n";
            $buffer .= $command . "\n";
            null !== $afterCommand && $buffer .= $afterCommand;
        }

        return $buffer;
    }

    /**
     * @param array|string $line
     *
     * @return string
     */
    private function generateCommand($line)
    {
        $standard = is_scalar($line) || null === $line;
        $pipe = is_array($line) && isset($line['pipe']) && is_string($line['pipe']);

        if ($standard) {
            return (string)$line;
        }

        $buffer = '';

        if ($pipe) {
            $buffer .= "echo \"pipe: {$line['pipe']} (pending feature)\" # pipe feature is pending\n";
            if (isset($line['variables']) && is_array($line['variables'])) {
                foreach ($line['variables'] as $name => $value) {
                    $buffer .= "echo '  ${name} (${value}):' ${value}\n";
                }
            }
        }

        return $buffer;
    }
}
