<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\Value\Prefix;
use UnexpectedValueException;

/**
 * Class NameBuilder
 *
 * @package Ktomk\Pipelines\Runner\Containers
 */
abstract class NameBuilder
{
    /**
     * @param string $string
     * @param string $replacement [optional] defaults to dash "-"
     * @param string $fallBack [optional] defaults to empty string
     *
     * @return string
     */
    public static function slugify($string, $replacement = null, $fallBack = null)
    {
        null === $replacement && $replacement = '-';

        // all non-allowed characters -> replacement (which is normally a separator "_", "." or "-")
        $buffer = preg_replace('([^a-zA-Z0-9_.-]+)', (string)$replacement, (string)$string);
        if (null === $buffer) {
            // @codeCoverageIgnoreStart
            throw new UnexpectedValueException('regex operation failed');
            // @codeCoverageIgnoreEnd
        }

        // multiple separator(s) after each other -> one replacement (which is normally a separator)
        $buffer = preg_replace('(([_.-])[_.-]+)', (string)$replacement, $buffer);

        // not starting nor ending with a separator
        $buffer = trim($buffer, '_.-');

        // not starting with a number
        // multiple separator(s) after each other -> one replacement (which is normally a separator)
        $buffer = preg_replace(array('(^\d+([_.-]\d+)*)', '(([_.-])[_.-]+)'), (string)$replacement, $buffer);

        // not starting nor ending with a separator
        $buffer = trim($buffer, '_.-');

        // separator(s) only -> empty string
        $buffer = preg_replace('(^[_.-]+$)', '', $buffer);

        return '' === (string)$buffer ? (string)$fallBack : (string)$buffer;
    }

    /**
     * service container name
     *
     * example: pipelines.service-redis.pipelines
     *              ^    `   ^   `  ^  `   ^
     *              |        |      |      |
     *              |    "service"  |   project
     *           prefix       service name
     *
     * @param string $prefix
     * @param string $service name
     * @param string $project name
     *
     * @return string
     */
    public static function serviceContainerName($prefix, $service, $project)
    {
        return self::slugify(
            sprintf(
                '%s.service-%s',
                $prefix,
                implode(
                    '.',
                    array(
                        self::slugify($service, '-', 'unnamed'),
                        $project,
                    )
                )
            )
        );
    }

    /**
     * step container name
     *
     * example: pipelines-1.pipeline-features-and-introspection.default.app
     *              ^    `^`                  ^                `    ^  ` ^
     *              |     |                   |                     |    |
     *              | step number        step name           pipeline id |
     *           prefix                                                project
     *
     * @param string $pipelineId
     * @param string $stepName
     * @param int $stepNumber (step numbers start at one)
     * @param string $prefix
     * @param string $project name
     *
     * @return string
     */
    public static function stepContainerName($pipelineId, $stepName, $stepNumber, $prefix, $project)
    {
        return self::slugify(
            sprintf(
                '%s-%s',
                Prefix::verify($prefix),
                implode(
                    '.',
                    array(
                        (string)(int)max(1, $stepNumber),
                        self::slugify($stepName, '-', 'no-name'),
                        self::slugify($pipelineId, '-', 'null'),
                        self::slugify($project),
                    )
                )
            ),
            ''
        );
    }

    /**
     * generate step container name
     *
     * @param Step $step
     * @param string $prefix
     * @param string $project name
     *
     * @return string
     *
     * @see StepContainer::generateName()
     */
    public static function stepContainerNameByStep(Step $step, $prefix, $project)
    {
        $pipelineId = $step->getPipeline()->getId();
        $stepName = (string)$step->getName();
        $stepNumber = $step->getIndex() + 1;

        return self::stepContainerName($pipelineId, $stepName, $stepNumber, $prefix, $project);
    }
}
