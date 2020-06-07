<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\File\Pipeline\Step;

/**
 * Class StepContainer
 *
 * @package Ktomk\Pipelines\Runner
 */
class StepContainer
{
    /**
     * @var null|string id of the (running) container
     */
    private $id;

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
     * StepContainer constructor.
     *
     * @param string $name
     * @param Step $step
     * @param Exec $exec
     */
    public function __construct($name, Step $step, Exec $exec)
    {
        $this->name = $name;
        $this->step = $step;
        $this->exec = $exec;
    }

    /**
     * the display id
     *
     *   side-effect: if id is null, this signals a dry-run which is made
     * visible by the string "*dry-run*"
     *
     * @return string
     */
    public function getDisplayId()
    {
        return isset($this->id) ? $this->id : '*dry-run*';
    }

    /**
     * @return null|string ID of (once) running container or null if not yet running
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|string name of the container, NULL if no name generated yet
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $keep a container on true, kill on false (if it exists)
     *
     * @return null|string
     */
    public function keepOrKill($keep)
    {
        $name = $this->name;
        if (null === $name) {
            throw new \BadMethodCallException('Container has no name yet');
        }

        $processManager = Docker::create($this->exec)->getProcessManager();

        if (false === $keep) {
            $processManager->zapContainersByName($name);

            return $this->id = null;
        }

        return $this->id = $processManager->findContainerIdByName($name);
    }

    /**
     * @param bool $kill
     * @param bool $remove
     *
     * @return void
     */
    public function killAndRemove($kill, $remove)
    {
        $id = $this->getDisplayId();

        Containers::execKillAndRemove($this->exec, $id, $kill, $remove);
    }

    /**
     * @param array $args
     *
     * @return array array(int $status, string $out, string $err)
     */
    public function run(array $args)
    {
        $execRun = Containers::execRun($this->exec, $args);
        $this->id = array_pop($execRun);

        return $execRun;
    }
}
