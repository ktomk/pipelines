<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\Runner\Containers;
use Ktomk\Pipelines\Runner\Runner;

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
     * @var Runner
     */
    private $runner;

    /**
     * @var StepServiceContainers
     */
    private $serviceContainers;

    /**
     * StepContainer constructor.
     *
     * @param string $name
     * @param Step $step
     * @param Runner $runner
     */
    public function __construct($name, Step $step, Runner $runner)
    {
        $this->name = $name;
        $this->step = $step;
        $this->runner = $runner;
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
     * @return StepServiceContainers
     */
    public function getServiceContainers()
    {
        return $this->serviceContainers
            = $this->serviceContainers ?: new StepServiceContainers($this->step, $this->runner);
    }

    /**
     * @param null|bool $keep a container on true, kill on false (if it exists)
     *
     * @return null|string
     */
    public function keepOrKill($keep = null)
    {
        $keep = null === $keep ? $this->runner->getFlags()->reuseContainer() : $keep;

        $name = $this->name;
        if (null === $name) {
            throw new \BadMethodCallException('Container has no name yet');
        }

        $processManager = Docker::create($this->runner->getExec())->getProcessManager();

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

        Containers::execKillAndRemove($this->runner->getExec(), $id, $kill, $remove);
    }

    /**
     * @param array $args
     *
     * @return array array(int $status, string $out, string $err)
     */
    public function run(array $args)
    {
        $execRun = Containers::execRun($this->runner->getExec(), $args);
        $this->id = array_pop($execRun);

        return $execRun;
    }

    /**
     * @param int $status
     *
     * @return void
     */
    public function shutdown($status)
    {
        $id = $this->getDisplayId();

        $message = sprintf(
            'keeping container id %s',
            (string)substr($id, 0, 12)
        );

        Containers::execShutdownContainer(
            $this->runner->getExec(),
            $this->runner->getStreams(),
            $id,
            $status,
            $this->runner->getFlags(),
            $message
        );

        $this->getServiceContainers()->shutdown($status);
    }

    /** docker run args mapping methods */

    /**
     * @param null|string $user
     *
     * @return array
     */
    public function obtainUserOptions()
    {
        $user = $this->runner->getRunOpts()->getUser();

        $userOpts = array();

        if (null === $user) {
            return $userOpts;
        }

        $userOpts = array('--user', $user);

        if (LibFs::isReadableFile('/etc/passwd') && LibFs::isReadableFile('/etc/group')) {
            $userOpts[] = '-v';
            $userOpts[] = '/etc/passwd:/etc/passwd:ro';
            $userOpts[] = '-v';
            $userOpts[] = '/etc/group:/etc/group:ro';
        }

        return $userOpts;
    }
}
