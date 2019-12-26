<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\File\Step;

/**
 * Class StepContainer
 *
 * @package Ktomk\Pipelines\Runner
 */
class StepContainer
{
    /**
     * @var null|string name of the container
     */
    private $name;

    /**
     * @var Step
     */
    private $step;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @param Step $step
     *
     * @return StepContainer
     */
    public static function create(Step $step, Exec $exec = null)
    {
        if (null === $exec) {
            $exec = new Exec();
        }

        return new self($step, $exec);
    }

    /**
     * @param Step $step
     * @param string $prefix
     * @param string $project name
     *
     * @return string
     */
    public static function createName(Step $step, $prefix, $project)
    {
        return self::create($step)->generateName($prefix, $project);
    }

    /**
     * StepContainer constructor.
     *
     * @param Step $step
     * @param Exec $exec
     */
    public function __construct(Step $step, Exec $exec)
    {
        $this->step = $step;
        $this->exec = $exec;
    }

    /**
     * generate step container name
     *
     * example: pipelines-1.pipeline-features-and-introspection.default.app
     *              ^    `^`                  ^                `    ^  ` ^
     *              |     |                   |                     |    |
     *              | step number        step name           pipeline id |
     *           prefix                                                project
     *
     * @param string $prefix for the name (normally "pipelines")
     * @param string $project name
     *
     * @return string
     */
    public function generateName($prefix, $project)
    {
        $step = $this->step;

        $idContainerSlug = preg_replace('([^a-zA-Z0-9_.-]+)', '-', $step->getPipeline()->getId());
        if ('' === $idContainerSlug) {
            $idContainerSlug = 'null';
        }
        $nameSlug = preg_replace(array('( )', '([^a-zA-Z0-9_.-]+)'), array('-', ''), $step->getName());
        if ('' === $nameSlug) {
            $nameSlug = 'no-name';
        }

        return $this->name = $prefix . '-' . implode(
            '.',
            array(
                $step->getIndex() + 1,
                $nameSlug,
                trim($idContainerSlug, '-'),
                $project,
            )
        );
    }

    /**
     * @return null|string name of the container, NULL if no name generated yet
     */
    public function getName()
    {
        return $this->name;
    }
}
