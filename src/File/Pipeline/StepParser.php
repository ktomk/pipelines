<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\Image;
use Ktomk\Pipelines\File\ParseException;

class StepParser
{
    public static function validate(array $step, array $env)
    {
        new StepParser($step, $env);
    }

    private function __construct(array $step, array $env)
    {
        // quick validation: image name
        Image::validate($step);

        // quick validation: trigger
        $this->validateTrigger($step, (bool)$env);

        // quick validation: script + after-script
        $this->parseScript($step);
        $this->parseAfterScript($step);
    }

    /**
     * validate step trigger (none, manual, automatic)
     *
     * @param array $array
     * @param bool $isParallelStep
     *
     * @return void
     */
    private function validateTrigger(array $array, $isParallelStep)
    {
        if (!array_key_exists('trigger', $array)) {
            return;
        }

        $trigger = $array['trigger'];
        if ($isParallelStep) {
            throw new ParseException("Unexpected property 'trigger' in parallel step");
        }

        if (!in_array($trigger, array('manual', 'automatic'), true)) {
            throw new ParseException("'trigger' expects either 'manual' or 'automatic'");
        }
    }

    /**
     * Parse a step script section
     *
     * @param array $step
     *
     * @throws ParseException
     *
     * @return void
     */
    private function parseScript(array $step)
    {
        if (!isset($step['script'])) {
            throw new ParseException("'step' requires a script");
        }
        $this->parseNamedScript('script', $step);
    }

    /**
     * @param array $step
     *
     * @return void
     */
    private function parseAfterScript(array $step)
    {
        if (isset($step['after-script'])) {
            $this->parseNamedScript('after-script', $step);
        }
    }

    /**
     * @param string $name
     * @param $script
     *
     * @return void
     */
    private function parseNamedScript($name, array $script)
    {
        if (!is_array($script[$name]) || !count($script[$name])) {
            throw new ParseException("'${name}' requires a list of commands");
        }

        foreach ($script[$name] as $index => $line) {
            $this->parseNamedScriptLine($name, $index, $line);
        }
    }

    /**
     * @param string $name
     * @param int $index
     * @param null|array|bool|float|int|string $line
     *
     * @return void
     */
    private function parseNamedScriptLine($name, $index, $line)
    {
        $standard = is_scalar($line) || null === $line;
        $pipe = is_array($line) && isset($line['pipe']) && is_string($line['pipe']);

        if (!($standard || $pipe)) {
            throw new ParseException(sprintf(
                "'%s' requires a list of commands, step #%d is not a command",
                $name,
                $index
            ));
        }
    }
}
