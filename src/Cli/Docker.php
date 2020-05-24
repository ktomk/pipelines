<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use UnexpectedValueException;

/**
 * Php wrapper for the Docker CLI
 */
class Docker
{
    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var string
     */
    private $name = 'docker';

    /**
     * @param null|Exec $exec [optional]
     *
     * @return Docker
     */
    public static function create(Exec $exec = null)
    {
        if (null === $exec) {
            $exec = new Exec();
        }

        return new self($exec);
    }

    public function __construct(Exec $exec)
    {
        $this->exec = $exec;
    }

    /**
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function hasCommand()
    {
        $status = $this->exec->capture(
            'command',
            array('-v', $this->name)
        );

        return 0 === $status;
    }

    /**
     * @throws \RuntimeException
     * @throws UnexpectedValueException
     *
     * @return null|string
     */
    public function getVersion()
    {
        $status = $this->exec->capture(
            $this->name,
            array('version', '--format', '{{.Server.Version}}'),
            $out
        );

        if (0 !== $status) {
            return null;
        }

        # parse version string
        $return = preg_match(
            '~^(\d+\\.\d+\\.\d+(?:-ce)?|master-dockerproject-20(?:19|[2-9]\d)-[01]\d-[0-3][1-9])\\n$~',
            $out,
            $matches
        );

        if (false === $return) {
            throw new UnexpectedValueException('Regex pattern failed'); // @codeCoverageIgnore
        }

        if (0 === $return) {
            trigger_error(
                sprintf('Failed to parse "%s" for Docker version', $out)
            );

            return '0.0.0-err';
        }

        return $matches[1];
    }

    /**
     * inspect a container for a mount on $mountPoint on the host system and
     * return it (the "device").
     *
     * @param string $container name or id to inspect
     * @param string $mountPoint absolute path in the container to search for a mount for
     *
     * @return null|string null if no such mount point in the container, path on host if
     */
    public function hostConfigBind($container, $mountPoint)
    {
        $exec = $this->exec;

        $status = $exec->capture('docker', array(
            'inspect', $container,
        ), $out);
        if (0 !== $status) {
            return null;
        }

        $data = json_decode($out, true);
        if (!isset($data[0]['HostConfig']['Binds'])) {
            return null;
        }

        $binds = $data[0]['HostConfig']['Binds'];

        foreach ($binds as $bind) {
            $pattern = sprintf('(^([^:]+):%s(?::ro)?$)', preg_quote($mountPoint, '()'));
            if (preg_match($pattern, $bind, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * inspect a container for a mount on $mountPoint on the host system and
     * return it (the "device").
     *
     * @param string $container name or id to inspect
     * @param string $mountPoint absolute path to search for a host mount for
     *
     * @return bool|string
     */
    public function hostDevice($container, $mountPoint)
    {
        $result = $this->hostConfigBind($container, $mountPoint);
        if (null === $result) {
            return $mountPoint;
        }

        return $result;
    }

    /**
     * @return Docker\ProcessManager
     */
    public function getProcessManager()
    {
        return new Docker\ProcessManager($this->exec);
    }
}
