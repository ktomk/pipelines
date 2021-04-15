<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Lib;

/**
 * Class DockerAbstractionLayer Implementation
 *
 * Implement all of the docker interaction (first of all from runners'
 * side) with the docker cli (the docker client binary) with a defined
 * interface. Any (new) interaction (inter!) with docker client
 * programmed against the interface so it can become part of the
 * runner component (for the interface) and inverse the dependency
 * against the hard working implementation.
 *
 * First implementation of the abstraction layer, as runner driven,
 * placed in there but perhaps must not belong into there but a
 * component of it's own (still exec driven, yet no passthru() in use,
 * hope to keep it, but it's a more leaking abstraction for pipelines,
 * so let's see how clean this can be done, in CLI context, passthru()
 * is especially useful for the overall pipelines utility, maybe when
 * breaking with it due to null-byte prevention leakage, see KNOWN BUGS)
 *
 * @package Ktomk\Pipelines\Runner\Docker
 */
class AbstractionLayerImpl implements AbstractionLayer
{
    /**
     * @var bool
     */
    private $throws;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * DockerAbstractionLayer constructor.
     *
     * @param Exec $exec
     * @param bool $throws optional
     */
    public function __construct(Exec $exec, $throws = null)
    {
        $this->exec = $exec;
        $this->throws($throws);
    }

    /**
     * @param string $id
     * @param array|string[] $arguments
     *
     * @return null|string null on error (non-zero exit status),
     *                     string with standard output (rtrim-med) on success
     */
    public function execute($id, array $arguments)
    {
        $status = $this->exec->capture(
            $cmd = Lib::cmd(
                'docker',
                Lib::merge('exec', $id, $arguments)
            ),
            array(),
            $out,
            $err
        );

        if (0 !== $status) {
            $this->notifyNonZero($cmd, $status, $out, $err);

            return null;
        }

        return rtrim($out);
    }

    public function kill($idOrName)
    {
        $status = $this->exec->capture(
            $cmd = Lib::cmd(
                'docker',
                Lib::merge('kill', $idOrName)
            ),
            array(),
            $out,
            $err
        );

        if (0 !== $status) {
            $this->notifyNonZero($cmd, $status, $out, $err);

            return null;
        }

        return rtrim($out);
    }

    /**
     * remove a container
     *
     * @param string $idOrName docker container
     * @param bool $force optional
     *
     * @return null|string
     */
    public function remove($idOrName, $force = true)
    {
        $status = $this->exec->capture(
            $cmd = Lib::cmd(
                'docker',
                Lib::merge('rm', $force ? '-f' : null, $idOrName)
            ),
            array(),
            $out,
            $err
        );

        // idempotent removal for throw behaviour, removing an nonexistent is not an exception, never
        if (1 === $status) {
            return null;
        }

        if (0 !== $status) {
            $this->notifyNonZero($cmd, $status, $out, $err);

            return null;
        }

        $buffer = rtrim($out);

        // idempotent removal for return behaviour, removing an nonexistent is not an exception, never
        // later docker exits 0, no out and err is that no such container to remove
        if ('' === $buffer && '' !== $err) {
            return null;
        }

        return $buffer;
    }

    public function start($image, array $arguments, array $runArguments = array())
    {
        $status = $this->exec->capture(
            $cmd = Lib::cmd(
                'docker',
                Lib::merge('run', array('--detach', '--entrypoint', '/bin/sh', '-it'), $arguments, $image, $runArguments)
            ),
            array(),
            $out,
            $err
        );

        if (0 !== $status) {
            $this->notifyNonZero($cmd, $status, $out, $err);

            return null;
        }

        return rtrim($out);
    }

    /* tar methods */

    public function importTar($tar, $id, $path)
    {
        $status = $this->exec->capture(
            $cmd = Lib::cmd(
                sprintf('<%s docker', Lib::quoteArg($tar)),
                array(
                    'cp',
                    '-',
                    sprintf('%s:%s', $id, $path),
                )
            ),
            array(),
            $out,
            $err
        );

        if (0 !== $status) {
            $this->notifyNonZero($cmd, $status, $out, $err);

            return null;
        }

        return true;
    }

    public function exportTar($id, $path, $tar)
    {
        $status = $this->exec->capture(
            $cmd = Lib::cmd(
                sprintf('>%s docker', Lib::quoteArg($tar)),
                array(
                    'cp',
                    sprintf('%s:%s', $id, $path),
                    '-',
                )
            ),
            array(),
            $out,
            $err
        );

        if (0 !== $status) {
            $this->notifyNonZero($cmd, $status, $out, $err);

            return null;
        }

        return $tar;
    }

    /* layer error/exception handling */

    public function throws($throws = null)
    {
        $this->throws = null === $throws
            ? $this->exec instanceof ExecTester
            : $throws;
    }

    /**
     * notify non zero status
     *
     * internal representation to throw, leaking as some non-zero values
     * for some of the abstractions are expected to simplify the interface
     * in which case the notification is not called.
     *
     * @param string $cmd
     * @param int $status
     * @param string $out
     * @param string $err
     */
    private function notifyNonZero($cmd, $status, $out, $err)
    {
        if (!$this->throws) {
            return;
        }

        throw new \RuntimeException(
            sprintf(
                "Failed to execute\n\n  %s\n\ngot status %d and error:\n\n  %s\n\nwith output:\n\n %s\n",
                $cmd,
                $status,
                $err,
                $out
            )
        );
    }
}
