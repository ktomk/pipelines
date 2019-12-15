<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\File\Step;

/**
 * Pipeline runner with docker under the hood
 */
class Runner
{
    const STATUS_NO_STEPS = 1;
    const STATUS_RECURSION_DETECTED = 127;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var Directories
     */
    private $directories;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var Flags
     */
    private $flags;

    /**
     * @var Env
     */
    private $env;
    /**
     * @var Streams
     */
    private $streams;

    /**
     * Runner constructor.
     *
     * @param string $prefix
     * @param Directories $directories source repository root directory based directories object
     * @param Exec $exec
     * @param Flags $flags [optional]
     * @param Env $env [optional]
     * @param Streams $streams [optional]
     */
    public function __construct(
        $prefix,
        Directories $directories,
        Exec $exec,
        Flags $flags = null,
        Env $env = null,
        Streams $streams = null
    )
    {
        $this->prefix = $prefix;
        $this->directories = $directories;
        $this->exec = $exec;
        $this->flags = null === $flags ? new Flags() : $flags;
        $this->env = null === $env ? Env::create() : $env;
        $this->streams = null === $streams ? Streams::create() : $streams;
    }

    /**
     * @param Pipeline $pipeline
     * @throws \RuntimeException
     * @return int status (as in exit status, 0 OK, !0 NOK)
     */
    public function run(Pipeline $pipeline)
    {
        $hasId = $this->env->setPipelinesId($pipeline->getId()); # TODO give Env an addPipeline() method (compare addReference)
        if ($hasId) {
            $this->streams->err(sprintf(
                "pipelines: won't start pipeline '%s'; pipeline inside pipelines recursion detected\n",
                $pipeline->getId()
            ));

            return self::STATUS_RECURSION_DETECTED;
        }

        foreach ($pipeline->getSteps() as $step) {
            $status = $this->runStep($step);
            if (0 !== $status) {
                return $status;
            }
        }

        if (!isset($status)) {
            $this->streams->err("pipelines: pipeline with no step to execute\n");

            return self::STATUS_NO_STEPS;
        }

        return $status;
    }

    /**
     * @param Step $step
     * @return int status (as in exit status, 0 OK, !0 NOK)
     */
    public function runStep(Step $step) {
        $stepRunner = new StepRunner(
            $this->prefix,
            $this->directories,
            $this->exec,
            $this->flags,
            $this->env,
            $this->streams
        );

        return $stepRunner->runStep($step);
    }
}
