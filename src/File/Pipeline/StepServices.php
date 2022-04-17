<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\Definitions\Service;
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
     * @psalm-var array<string, int>
     */
    private $services;

    /**
     * StepServices constructor.
     *
     * @param Step $step
     * @param null|array|mixed $services
     *
     * @return void
     */
    public function __construct(Step $step, $services)
    {
        // quick validation: script
        $parsed = $this->parseServices($services);

        $this->step = $step;
        $this->services = array_flip($parsed);
    }

    /**
     * @param string $service
     *
     * @return bool
     */
    public function has($service)
    {
        return isset($this->services[$service]);
    }

    /**
     * get definitions of all step services
     *
     * get all service definitions of the step services, docker is not
     * of interest here as only looking for standard services, might
     * be subject to change {@see getServiceNames()}.
     *
     * @return Service[]
     */
    public function getDefinitions()
    {
        return $this->step->getFile()->getDefinitions()->getServices()->getByNames($this->getServiceNames());
    }

    /**
     * @return array|string[]
     */
    public function getServiceNames()
    {
        $standard = $this->services;
        unset($standard['docker']);

        return array_keys($standard);
    }

    /**
     * parse services
     *
     * @param null|array|mixed $services
     *
     * @return string[]
     */
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
