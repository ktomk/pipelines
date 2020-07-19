<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

/**
 * Flags for use in Runner
 */
class Flags
{
    /**
     * default flags
     *
     * 19: FLAG_DOCKER_REMOVE (1) | FLAG_DOCKER_KILL (2) | FLAG_SOCKET (16)
     */
    const FLAGS = 19;

    /**
     * remove docker container after pipeline run
     */
    const FLAG_DOCKER_REMOVE = 1;

    /**
     * kill running docker container after pipeline run
     */
    const FLAG_DOCKER_KILL = 2;

    /**
     * use copy to deploy project into pipeline container
     */
    const FLAG_DEPLOY_COPY = 4; # copy working dir into container

    /**
     * keep pipeline container in case of error (non zero exit status)
     */
    const FLAG_KEEP_ON_ERROR = 8;

    /**
     * mount docker daemon socket into pipeline container (docker in docker)
     */
    const FLAG_SOCKET = 16;

    /**
     * @var int flags bit-mask value
     */
    public $memory = self::FLAGS;

    /**
     * Flags constructor.
     *
     * @param int $memory [optional]
     */
    public function __construct($memory = null)
    {
        null === $memory || $this->memory = $memory;
    }

    /**
     * @return bool
     */
    public function deployCopy()
    {
        return (bool)($this->memory & self::FLAG_DEPLOY_COPY);
    }

    /**
     * @return bool
     */
    public function keep()
    {
        return !($this->memory & (self::FLAG_DOCKER_KILL | self::FLAG_DOCKER_REMOVE));
    }

    /**
     * @return bool
     */
    public function keepOnError()
    {
        return (bool)($this->memory & self::FLAG_KEEP_ON_ERROR);
    }

    /**
     * @return bool
     */
    public function killContainer()
    {
        return (bool)($this->memory & self::FLAG_DOCKER_KILL);
    }

    /**
     * @return bool
     */
    public function removeContainer()
    {
        return (bool)($this->memory & self::FLAG_DOCKER_REMOVE);
    }

    /**
     * @return bool
     */
    public function reuseContainer()
    {
        return
            ($this->memory & self::FLAG_KEEP_ON_ERROR)
            || !($this->memory & (self::FLAG_DOCKER_KILL | self::FLAG_DOCKER_REMOVE));
    }

    /**
     * @return bool
     */
    public function useDockerSocket()
    {
        return (bool)($this->memory & self::FLAG_SOCKET);
    }

    /**
     * @param int $flag
     *
     * @return bool
     */
    public function flgHas($flag)
    {
        return (bool)($this->memory & $flag);
    }
}
