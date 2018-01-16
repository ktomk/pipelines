<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\Runner\Env;

/**
 * Pipeline runner with docker under the hood
 */
class Runner
{
    const FLAGS = 3;
    const FLAG_DOCKER_REMOVE = 1;
    const FLAG_DOCKER_KILL = 2;
    const FLAG_DEPLOY_COPY = 4; # copy working dir into container

    const STATUS_NO_STEPS = 1;
    const STATUS_RECURSION_DETECTED = 127;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var int
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
     * DockerSession constructor.
     *
     * @param string $prefix
     * @param string $directory source repository root
     * @param Exec $exec
     * @param int $flags [optional]
     * @param Env $env [optional]
     * @param Streams $streams [optional]
     */
    public function __construct(
        $prefix,
        $directory,
        Exec $exec,
        $flags = null,
        Env $env = null,
        Streams $streams = null
    )
    {
        $this->prefix = $prefix;

        $this->directory = $directory;
        $this->exec = $exec;
        $this->flags = $flags === null ? self::FLAGS : $flags;
        $this->env = null === $env ? Env::create() : $env;
        $this->streams = null === $streams ? Streams::create() : $streams;
    }

    public function run(Pipeline $pipeline)
    {
        $hasId = $this->env->setPipelinesId($pipeline->getId()); # TODO give Env an addPipeline() method (compare addReference)
        if ($hasId) {
            $this->streams->err(sprintf(
                "fatal: won't start pipeline '%s'; pipeline inside pipelines recursion detected\n",
                $pipeline->getId()
            ));
            return self::STATUS_RECURSION_DETECTED;
        }

        $steps = $pipeline->getSteps();
        foreach ($steps as $index => $step) {
            $status = $this->runStep($step, $index);
            if ($status !== 0) {
                return $status;
            }
        }

        if (!isset($status)) {
            $this->streams->err("error: pipeline with no step to execute\n");
            return self::STATUS_NO_STEPS;
        }

        return $status;
    }

    /**
     * @param Step $step
     * @param int $index
     * @return int exit status
     */
    public function runStep(Step $step, $index)
    {
        $prefix = $this->prefix;
        $dir = $this->directory;
        $env = $this->env;
        $exec = $this->exec;
        $streams = $this->streams;

        $docker = new Docker($exec);

        $name = $prefix . '-' . Lib::generateUuid();
        $image = $step->getImage();
        $env->setContainerName($name);

        # launch container
        $streams->out(sprintf(
            "\x1D+++ step #%d\n\n    name...........: %s\n    effective-image: %s\n    container......: %s\n",
            $index,
            $step->getName() ? '"' . $step->getName() . '"' : '(unnamed)',
            $step->getImage(),
            $name
        ));

        $copy = (bool)($this->flags & self::FLAG_DEPLOY_COPY);

        // docker client inside docker
        // FIXME give controlling options, this is serious /!\
        $mountDockerSock = array();
        if (file_exists('/var/run/docker.sock')) {
            $mountDockerSock = array(
                '-v', '/var/run/docker.sock:/var/run/docker.sock',
            );
        }

        $parentName = $env->getValue('PIPELINES_PARENT_CONTAINER_NAME');
        $checkMount = $mountDockerSock && null !== $parentName;
        $deviceDir = $checkMount ? $docker->hostDevice($parentName, $dir) : $dir;

        $mountWorkingDirectory = $copy
            ? array()
            : array('--volume', "$deviceDir:/app");

        $status = $exec->capture('docker', array(
            'run', '-i', '--name', $name,
            $env->getArgs('-e'),
            $mountWorkingDirectory, '-e', 'BITBUCKET_CLONE_DIR=/app',
            $mountDockerSock,
            '--workdir', '/app', '--detach', $image
        ), $out, $err);
        if ($status !== 0) {
            $streams->out(sprintf("    container-id...: %s\n\n", '*failure*'));
            $streams->out(sprintf("fatal: setting up the container failed.\n"));
            $streams->err(sprintf("%s\n", $err));
            $streams->out(sprintf("%s\n", $out));
            $streams->out(sprintf("exit status: %d\n", $status));
            return $status;
        }
        $id = rtrim($out) ?: "*dry-run*"; # side-effect: internal exploit of no output with true exit status
        $streams->out(sprintf("    container-id...: %s\n\n", substr($id, 0, 12)));

        # TODO: different deployments, mount (default), mount-ro, copy
        if ($copy) {
            $streams->out("\x1D+++ copying files into container...\n");
            $status = $exec->pass('docker', array(
                    'cp', '-a', $dir . '/.', $id . ':/app')
            );
            if ($status !== 0) {
                $streams->err('Deploy copy failure\n');
                return $status;
            }
            $streams("");
        }

        $script = $step->getScript();
        foreach ($script as $line => $command) {
            $streams->out(sprintf("\x1D+ %s\n", $command));
            $status = $exec->pass('docker', array(
                'exec', '-i', $name, '/bin/sh', '-c', $command,
            ));
            $streams->out(sprintf("\n"));
            if ($status !== 0) {
                break;
            }
        }

        if (0 !== $status) {
            # keep container
            $this->streams->err(sprintf(
                "exit status %d, keeping container id %s\n",
                $status,
                substr($id, 0, 12)
            ));
        } else {
            # remove container
            if ($this->flags & self::FLAG_DOCKER_KILL) {
                $exec->capture('docker', array('kill', $name));
            }
            if ($this->flags & self::FLAG_DOCKER_REMOVE) {
                $exec->capture('docker', array('rm', $name));
            }
        }

        return $status;
    }
}
