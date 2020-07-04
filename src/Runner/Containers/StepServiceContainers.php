<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\Runner\Containers;
use Ktomk\Pipelines\Runner\Runner;

/**
 * Class StepServiceContainers
 *
 * @package Ktomk\Pipelines\Runner\Containers
 */
class StepServiceContainers
{
    /**
     * @var Step
     */
    private $step;
    /**
     * @var Runner
     */
    private $runner;

    public function __construct(Step $step, Runner $runner)
    {
        $this->step = $step;
        $this->runner = $runner;
    }

    /**
     * run all service containers and obtain network configuration (if any)
     *
     * @return array docker run options for network (needed if there are services)
     *
     * @see StepRunner::runNewContainer()
     */
    public function obtainNetwork()
    {
        $step = $this->step;

        $services = (array)$step->getServices()->getDefinitions();

        $network = array();

        $labels = LabelsBuilder::createFromRunner($this->runner);

        foreach ($services as $name => $service) {
            list(, $network) = Containers::execRunServiceContainer(
                $this->runner->getExec(),
                $service,
                $this->runner->getEnv()->getResolver(),
                $this->runner->getRunOpts()->getPrefix(),
                $this->runner->getProject(),
                $labels->setRole('service')->toArray()
            );
        }

        return $network;
    }

    /**
     * @param int $status
     *
     * @return void
     */
    public function shutdown($status)
    {
        $step = $this->step;

        $services = (array)$step->getServices()->getDefinitions();

        foreach ($services as $name => $service) {
            $name = NameBuilder::serviceContainerName(
                $this->runner->getRunOpts()->getPrefix(),
                $service->getName(),
                $this->runner->getProject()
            );

            Containers::execShutdownContainer(
                $this->runner->getExec(),
                $this->runner->getStreams(),
                "/${name}",
                $status,
                $this->runner->getFlags(),
                sprintf('keeping service container %s', $name)
            );
        }
    }
}
