<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\ParseException;

/**
 * Class StepServices
 *
 * Services entry in a step
 *
 * @package Ktomk\Pipelines\File\File
 */
class StepServices
{
    /**
     * @var Step
     */
    private $step;

    /**
     * @var array
     */
    private $services;

    public function __construct(Step $step, $services)
    {
        // quick validation: script
        $parsed = $this->parseServices($services);

        $this->step = $step;
        $this->services = array_flip($parsed);
    }

    /**
     * @param string $service
     * @return bool
     */
    public function has($service)
    {
        return isset($this->services[$service]);
    }

    private function parseServices($services)
    {
        if (!is_array($services)) {
            throw new ParseException("'services' requires a list of services");
        }

        $reservoir = array();
        foreach ($services as $service) {
            if (!is_string($service)) {
                throw new ParseException("'services' service name string expected");
            }

            '' === ($service = trim($service)) || $reservoir[] = $service;
         }

        return $reservoir;
    }
}
