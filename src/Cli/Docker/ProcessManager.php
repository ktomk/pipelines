<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Docker;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Lib;

/**
 * Process manager for docker container
 */
class ProcessManager
{
    /**
     * @var Exec
     */
    private $exec;

    public function __construct(Exec $exec)
    {
        $this->exec = $exec;
    }

    /**
     * @param string $name
     *
     * @return null|array|string[] 0 or more ids, null if subsystem
     *         unavailable, subsystem error or unsupported parameter
     */
    public function findAllContainerIdsByName($name)
    {
        $ids = null;

        $status = $this->exec->capture(
            'docker',
            array(
                'ps', '--no-trunc', '-qa', '--filter',
                "name=^/\\Q${name}\\E$",
            ),
            $result
        );

        $status || $ids = Lib::lines($result);

        return $ids;
    }

    /**
     * container ids by name prefix of running and stopped containers
     *
     * @param string $prefix
     *
     * @return null|array of ids, null if an internal error occurred
     */
    public function findAllContainerIdsByNamePrefix($prefix)
    {
        return $this->psPrefixImpl($prefix, true);
    }

    /**
     * @param string $name
     *
     * @return null|string
     */
    public function findContainerIdByName($name)
    {
        $ids = $this->findAllContainerIdsByName($name);
        if (null === $ids || 1 !== count($ids)) {
            return null;
        }

        return $ids[0];
    }

    /**
     * container ids by name prefix of running containers
     *
     * @param string $prefix
     *
     * @return null|array of ids, null if an internal error occurred
     */
    public function findRunningContainerIdsByNamePrefix($prefix)
    {
        return $this->psPrefixImpl($prefix);
    }

    /**
     * @param string|string[] $idOrIds
     *
     * @return int
     */
    public function kill($idOrIds)
    {
        return $this->exec->capture('docker', Lib::merge('kill', func_get_args()));
    }

    /**
     * @param string|string[] $idOrIds
     *
     * @return int
     */
    public function remove($idOrIds)
    {
        return $this->exec->capture('docker', Lib::merge('rm', func_get_args()));
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function zapContainersByName($name)
    {
        $ids = $this->findAllContainerIdsByName($name);
        if (!$ids) {
            return;
        }
        $this->zap($ids);
    }

    /**
     * @param string|string[] $idOrIds
     *
     * @return void
     */
    public function zap($idOrIds)
    {
        $this->kill($idOrIds);
        $this->remove($idOrIds);
    }

    /**
     * @param string $prefix beginning of container name
     * @param bool $all
     *
     * @return null|array
     */
    private function psPrefixImpl($prefix, $all = false)
    {
        $ids = null;

        if (0 === preg_match('(^[a-z}{3,}[a-zA-Z0-9_.-]+$)', $prefix)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid container name prefix "%s"', $prefix)
            );
        }

        $status = $this->exec->capture(
            'docker',
            array(
                'ps', '-q' . ($all ? 'a' : ''), '--no-trunc', '--filter',
                "name=^/${prefix}[-.]",
            ),
            $result
        );

        $status || $ids = Lib::lines($result);

        return $ids;
    }
}
