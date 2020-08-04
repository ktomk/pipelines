<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Utility\CacheOptions;
use Ktomk\Pipelines\Utility\KeepOptions;

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
     * do not make use of dependency caches
     */
    const FLAG_NO_CACHE = 32;

    /**
     * @var int flags bit-mask value
     */
    public $memory = self::FLAGS;

    /**
     * Map diverse parameters to run flags
     *
     * @param KeepOptions $keep
     * @param string $deployMode
     * @param CacheOptions $cache
     *
     * @return Flags
     */
    public static function createForUtility(KeepOptions $keep, $deployMode, CacheOptions $cache)
    {
        $flagsValue = Flags::FLAGS;
        if ($keep->errorKeep) {
            $flagsValue |= Flags::FLAG_KEEP_ON_ERROR;
        } elseif ($keep->keep) {
            $flagsValue &= ~(Flags::FLAG_DOCKER_KILL | Flags::FLAG_DOCKER_REMOVE);
        }

        $cache->hasCache() || $flagsValue |= Flags::FLAG_NO_CACHE;

        if ('copy' === $deployMode) {
            $flagsValue |= Flags::FLAG_DEPLOY_COPY;
        }

        return new Flags($flagsValue);
    }

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
        return ($this->memory & self::FLAG_KEEP_ON_ERROR)
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
     * @return bool
     */
    public function noCache()
    {
        return (bool)($this->memory & self::FLAG_NO_CACHE);
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
