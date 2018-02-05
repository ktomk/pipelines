<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\Image;
use Ktomk\Pipelines\Runner\ArtifactSource;
use Ktomk\Pipelines\Runner\DockerLogin;
use Ktomk\Pipelines\Runner\Env;

/**
 * Pipeline runner with docker under the hood
 */
class Runner
{
    const FLAGS = 11;
    const FLAG_DOCKER_REMOVE = 1;
    const FLAG_DOCKER_KILL = 2;
    const FLAG_DEPLOY_COPY = 4; # copy working dir into container
    const FLAG_KEEP_ON_ERROR = 8;

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
                "pipelines: won't start pipeline '%s'; pipeline inside pipelines recursion detected\n",
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
            $this->streams->err("pipelines: pipeline with no step to execute\n");
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
            $image->getName(),
            $name
        ));

        # process docker login if image demands so, but continue on failure
        $this->imageLogin($image);

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
            : array('--volume', "$deviceDir:/app"); // FIXME(tk): hard encoded /app

        $status = $exec->capture('docker', array(
            'run', '-i', '--name', $name,
            $env->getArgs('-e'),
            $mountWorkingDirectory, '-e', 'BITBUCKET_CLONE_DIR=/app',
            $mountDockerSock,
            '--workdir', '/app', '--detach', $image->getName()
        ), $out, $err);
        if ($status !== 0) {
            $streams->out("    container-id...: *failure*\n\n");
            $streams->err("pipelines: setting up the container failed\n");
            $streams->err("$err\n");
            $streams->out("$out\n");
            $streams->out(sprintf("exit status: %d\n", $status));
            return $status;
        }
        $id = rtrim($out) ?: "*dry-run*"; # side-effect: internal exploit of no output with true exit status
        $streams->out(sprintf("    container-id...: %s\n\n", substr($id, 0, 12)));

        # TODO: different deployments, mount (default), mount-ro, copy
        if (null !== $result = $this->deployCopy($copy, $id, $dir)) {
            return $result;
        }

        $status = $this->runStepScript($step, $streams, $exec, $name);

        $this->captureStepArtifacts($step, $copy, $id, $dir);

        $this->shutdownStepContainer($status, $id, $exec, $name);

        return $status;
    }

    /**
     * @param Image $image
     */
    private function imageLogin(Image $image)
    {
        $login = new DockerLogin($this->exec, $this->env->getResolver());
        $login->byImage($image);
    }

    /**
     * @param bool $copy
     * @param string $id container id
     * @param string $dir directory to copy contents into container
     * @return int|null null if all clear, integer for exit status
     */
    private function deployCopy($copy, $id, $dir)
    {
        if (!$copy) {
            return null;
        }

        $streams = $this->streams;
        $exec = $this->exec;

        $streams->out("\x1D+++ copying files into container...\n");
        $status = $exec->pass('docker', array(
                'cp', '-a', $dir . '/.', $id . ':/app')
        );
        if ($status !== 0) {
            $streams->err('pipelines: deploy copy failure\n');
            return $status;
        }

        $streams("");

        return null;
    }

    /**
     * @param Step $step
     * @param Streams $streams
     * @param Exec $exec
     * @param string $name container name
     * @return int|null should never be null, status, non-zero if a command failed
     */
    private function runStepScript(Step $step, Streams $streams, Exec $exec, $name)
    {
        $script = $step->getScript();
        $status = null;

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

        return $status;
    }

    /**
     * @param Step $step
     * @param bool $copy
     * @param string $id container id
     * @param string $dir to put artifacts in (project directory)
     */
    private function captureStepArtifacts(Step $step, $copy, $id, $dir)
    {
        # capturing artifacts is only supported for deploy copy
        if (!$copy) {
            return;
        }

        $artifacts = $step->getArtifacts();

        if (null === $artifacts) {
            return;
        }

        $exec = $this->exec;
        $streams = $this->streams;

        $streams->out("\x1D+++ copying artifacts from container...\n");

        $source = new ArtifactSource($exec, $id, $dir);

        $patterns = $artifacts->getPatterns();
        foreach ($patterns as $pattern) {
            $this->captureArtifactPattern($source, $pattern, $id, $dir);
        }

        $streams("");
    }

    /**
     * @see Runner::captureStepArtifacts()
     *
     * @param ArtifactSource $source
     * @param string $pattern
     * @param string $id
     * @param string $dir
     */
    private function captureArtifactPattern(ArtifactSource $source, $pattern, $id, $dir)
    {
        $exec = $this->exec;
        $streams = $this->streams;

        $paths = $source->findByPattern($pattern);
        if (empty($paths)) {
            return;
        }

        $tar = Lib::cmd('tar', array('c', '-f', '-', $paths));
        $docker = Lib::cmd('docker', array('exec', '-w', '/app', $id));
        $unTar = Lib::cmd('tar', array('x', '-f', '-', '-C', $dir));

        $status = $exec->pass($docker . ' ' . $tar . ' | ' . $unTar, array());

        if ($status !== 0) {
            $streams->err(
                sprintf("Artifact failure: '%s'\n", $pattern)
            );
        }
    }

    /**
     * @param int $status
     * @param string $id container id
     * @param Exec $exec
     * @param string $name container name
     */
    private function shutdownStepContainer($status, $id, Exec $exec, $name)
    {
        $flags = $this->flags;

        # keep container on error
        if (0 !== $status && $flags & self::FLAG_KEEP_ON_ERROR) {
            $this->streams->err(sprintf(
                "exit status %d, keeping container id %s\n",
                $status,
                substr($id, 0, 12)
            ));

            return;
        }

        # keep or remove container
        if ($flags & self::FLAG_DOCKER_KILL) {
            $exec->capture('docker', array('kill', $name));
        }

        if ($flags & self::FLAG_DOCKER_REMOVE) {
            $exec->capture('docker', array('rm', $name));
        }
    }
}
