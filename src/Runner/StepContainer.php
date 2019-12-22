<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\File\Step;

/**
 * Class StepContainer
 *
 * @package Ktomk\Pipelines\Runner
 */
class StepContainer
{
    /**
     * @var Step
     */
    private $step;

    /**
     * @param Step $step
     *
     * @return StepContainer
     */
    public static function create(Step $step)
    {
        return new self($step);
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
     */
    public function __construct(Step $step)
    {
        $this->step = $step;
    }

    /**
     * @param string $prefix
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

        return $prefix . '-' . implode(
            '.',
            array(
                $step->getIndex() + 1,
                $nameSlug,
                trim($idContainerSlug, '-'),
                $project,
            )
        );
    }
}
