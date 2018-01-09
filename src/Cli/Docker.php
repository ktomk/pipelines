<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

/**
 * Php wrapper for the Docker CLI
 *
 */
class Docker
{
    /**
     * @var Exec
     */
    private $exec;

    private $name = 'docker';

    public function __construct(Exec $exec)
    {
        $this->exec = $exec;
    }

    public function hasCommand()
    {
        $status = $this->exec->capture(
            'command',
            array('-v', $this->name)
        );

        return 0 === $status;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        if (false === $this->hasCommand()) {
            return null;
        }

        $status = $this->exec->capture(
            $this->name,
            array('version', '--format', '{{.Server.Version}}'),
            $buffer
        );

        if ($status !== 0) {
            return null;
        }

        # parse version string
        $return = preg_match(
            '~^(\d+\\.\d+\\.\d+(?:-ce)?)\\n$~',
            $buffer,
            $matches
        );

        if (false === $return) {
            throw new \UnexpectedValueException('Regex pattern failed'); // @codeCoverageIgnore
        }

        if (0 === $return) {
            trigger_error(
                sprintf('Failed to parse "%s" for Docker version', $buffer)
            );
            return "0.0.0-err";
        }

        return $matches[1];
    }

    /**
     * inspect a container for a mount on $mountPoint on the host system and
     * return it (the "device").
     *
     * @param string $container name to inspect
     * @param string $mountPoint absolute path to search for a host mount for
     * @return bool|string
     */
    public function hostDevice($container, $mountPoint)
    {
        $exec = $this->exec;

        $status = $exec->capture('docker', array(
            'inspect', $container
        ), $out);
        if ($status !== 0) {
            return $mountPoint;
        }

        $data = json_decode($out, true);
        if (!isset($data[0]['HostConfig']['Binds'])) {
            return $mountPoint;
        }

        $binds = $data[0]['HostConfig']['Binds'];

        $end = ":$mountPoint";

        foreach ($binds as $bind) {
            if (substr($bind, -strlen($end)) === $end) {
                $hostDir = substr($bind, 0, -strlen($end));
                return $hostDir;
            }
        }

        return $mountPoint;
    }
}
