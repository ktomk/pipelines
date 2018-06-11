<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\Lib;

/**
 * Process manager for docker container
 */
class DockerProcessManager
{
    /**
     * @var Exec
     */
    private $exec;

    public function __construct(Exec $exec = null)
    {
        if (null === $exec) {
            $exec = new Exec();
        }

        $this->exec = $exec;
    }

    /**
     * container ids by name prefix of running and stopped containers
     *
     * @param string $prefix
     * @return null|array of ids, null if an internal error occurred
     */
    public function findAllContainerIdsByNamePrefix($prefix)
    {
        return $this->psPrefixImpl($prefix, true);
    }

    /**
     * container ids by name prefix of running containers
     *
     * @param string $prefix
     * @return null|array of ids, null if an internal error occurred
     */
    public function findRunningContainerIdsByNamePrefix($prefix)
    {
        return $this->psPrefixImpl($prefix);
    }

    /**
     * @param string|string[] $idOrIds
     * @return int
     */
    public function kill($idOrIds)
    {
        return $this->exec->capture('docker', Lib::merge('kill', func_get_args()));
    }

    /**
     * @param string|string[] $idOrIds
     * @return int
     */
    public function remove($idOrIds)
    {
        return $this->exec->capture('docker', Lib::merge('rm', func_get_args()));
    }

    /**
     * @param string $prefix
     * @param bool $all
     * @return null|array
     */
    private function psPrefixImpl($prefix, $all = false)
    {
        $ids = null;

        if (0 === preg_match('(^[a-zA-Z0-9][a-zA-Z0-9_.-]+$)', $prefix)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid container name prefix "%s"', $prefix)
            );
        }

        $status = $this->exec->capture(
            'docker',
            array(
                'ps', '-q' . ($all ? 'a' : ''), '--no-trunc', '--filter',
                "name=^/${prefix}"
            ),
            $result
        );

        $status || $ids = Lib::lines($result);

        return $ids;
    }
}
