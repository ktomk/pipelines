<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\Lib;

/**
 * Class StepScriptWriter
 *
 * Writes a pipeline step script as string. In use for script and after-script.
 *
 * @package Ktomk\Pipelines\Runner
 */
class StepScriptWriter
{
    /**
     * @param array|string[] $script
     * @param int $status exit status
     */
    public static function writeAfterScript(array $script, $status = 0)
    {
        $buffer = "# this /bin/sh script is generated from a pipeline after-script\n";
        $buffer .= "set -e\n";
        $buffer .= sprintf("BITBUCKET_EXIT_CODE=%d\n", $status);
        $buffer .= "export BITBUCKET_EXIT_CODE\n";
        $buffer .= self::generateScriptBody($script);

        return $buffer;
    }

    /**
     * generate step script from Step
     *
     * @param array|string[] $script
     * @param bool $scriptExitEarly the 'script.exit-early' configuration setting
     *
     * @return string
     */
    public static function writeStepScript(array $script, $scriptExitEarly = false)
    {
        $buffer = "# this /bin/sh script is generated from a pipeline script\n";
        $buffer .= "set -e\n";
        $buffer .= 'test "$0" = "/bin/bash" && if [ -f ~/.bashrc ]; then source ~/.bashrc; fi' . "\n";
        $buffer .= self::generateScriptBody(
            $script,
            self::getLinePostCommand($scriptExitEarly)
        );

        return $buffer;
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
    public static function getLinePostCommand($strict)
    {
        return $strict ? '( r=$?; if [ $r -ne 0 ]; then exit $r; fi; ) || exit' . "\n" : null;
    }

    /**
     * @param array|string[] $script
     * @param null|string $afterCommand
     *
     * @return string
     */
    public static function generateScriptBody(array $script, $afterCommand = null)
    {
        $buffer = '';
        foreach ($script as $line) {
            $command = self::generateCommand($line);
            $line && $buffer .= 'printf \'\\n\'' . "\n";
            $buffer .= 'printf \'\\035+ %s\\n\' ' . Lib::quoteArg($command) . "\n";
            $buffer .= $command . "\n";
            null !== $afterCommand && $buffer .= $afterCommand;
        }

        return $buffer;
    }

    /**
     * @param null|array|string $line
     *
     * @return string
     */
    public static function generateCommand($line)
    {
        $standard = is_scalar($line) || null === $line;
        $pipe = is_array($line) && isset($line['pipe']) && is_string($line['pipe']);

        if ($standard) {
            /** @var null|float|int|string $line */
            return (string)$line;
        }

        $line = (array)$line;
        $buffer = '';

        if ($pipe) {
            $buffer .= "echo \"pipe: {$line['pipe']} (pending feature)\" # pipe feature is pending\n";
            if (isset($line['variables']) && is_array($line['variables'])) {
                foreach ($line['variables'] as $name => $value) {
                    $buffer .= sprintf(
                        "printf %%s %s; printf '%%s ' %s; printf '\\n' \n",
                        Lib::quoteArg("  ${name} (${value}): "),
                        $value
                    );
                }
            }
        }

        return $buffer;
    }
}
