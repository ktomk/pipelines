<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\Image;
use Ktomk\Pipelines\Runner\ArtifactSource;
use Ktomk\Pipelines\Runner\Directories;
use Ktomk\Pipelines\Runner\DockerLogin;
use Ktomk\Pipelines\Runner\Env;

/**
 * Pipeline runner with docker under the hood
 */
class Runner
{
    const FLAGS = 19;
    const FLAG_DOCKER_REMOVE = 1;
    const FLAG_DOCKER_KILL = 2;
    const FLAG_DEPLOY_COPY = 4; # copy working dir into container
    const FLAG_KEEP_ON_ERROR = 8;
    const FLAG_SOCKET = 16;

    const STATUS_NO_STEPS = 1;
    const STATUS_RECURSION_DETECTED = 127;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var Directories
     */
    private $directories;

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
     * @param Directories $directories source repository root directory based directories object
     * @param Exec $exec
     * @param int $flags [optional]
     * @param Env $env [optional]
     * @param Streams $streams [optional]
     */
    public function __construct(
        $prefix,
        Directories $directories,
        Exec $exec,
        $flags = null,
        Env $env = null,
        Streams $streams = null
    )
    {
        $this->prefix = $prefix;
        $this->directories = $directories;
        $this->exec = $exec;
        $this->flags = null === $flags ? self::FLAGS : $flags;
        $this->env = null === $env ? Env::create() : $env;
        $this->streams = null === $streams ? Streams::create() : $streams;
    }

    /**
     * @param Pipeline $pipeline
     * @throws \RuntimeException
     * @return int status (as in exit status, 0 OK, !0 NOK)
     */
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

        foreach ($pipeline->getSteps() as $step) {
            $status = $this->runStep($step);
            if (0 !== $status) {
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
     * @throws \RuntimeException
     * @return int exit status
     */
    public function runStep(Step $step)
    {
        $dir = $this->directories->getProject();
        $env = $this->env;
        $exec = $this->exec;
        $streams = $this->streams;

        $docker = new Docker($exec);

        $name = $this->generateContainerName($step);
        $this->zapContinerByName($name);
        $image = $step->getImage();
        $env->setContainerName($name);

        # launch container
        $streams->out(sprintf(
            "\x1D+++ step #%d\n\n    name...........: %s\n    effective-image: %s\n    container......: %s\n",
            $step->getIndex() + 1,
            $step->getName() ? '"' . $step->getName() . '"' : '(unnamed)',
            $image->getName(),
            $name
        ));

        # process docker login if image demands so, but continue on failure
        $this->imageLogin($image);

        $copy = (bool)($this->flags & self::FLAG_DEPLOY_COPY);

        // enable docker client inside docker by mounting docker socket
        // FIXME give controlling options, this is serious /!\
        $socket = (bool)($this->flags & self::FLAG_SOCKET);
        $mountDockerSock = array();
        if ($socket && file_exists('/var/run/docker.sock')) {
            $mountDockerSock = array(
                '-v', '/var/run/docker.sock:/var/run/docker.sock',
            );
        }

        $parentName = $env->getValue('PIPELINES_PARENT_CONTAINER_NAME');
        $checkMount = $mountDockerSock && null !== $parentName;
        $deviceDir = $checkMount ? $docker->hostDevice($parentName, $dir) : $dir;

        $mountWorkingDirectory = $copy
            ? array()
            : array('--volume', "${deviceDir}:/app"); // FIXME(tk): hard encoded /app

        $status = $exec->capture('docker', array(
            'run', '-i', '--name', $name,
            $env->getArgs('-e'),
            $mountWorkingDirectory, '-e', 'BITBUCKET_CLONE_DIR=/app',
            $mountDockerSock,
            '--workdir', '/app', '--detach', $image->getName()
        ), $out, $err);
        if (0 !== $status) {
            $streams->out("    container-id...: *failure*\n\n");
            $streams->err("pipelines: setting up the container failed\n");
            $streams->err("${err}\n");
            $streams->out("${out}\n");
            $streams->out(sprintf("exit status: %d\n", $status));

            return $status;
        }
        $id = rtrim($out) ?: "*dry-run*"; # side-effect: internal exploit of no output with true exit status
        $streams->out(sprintf("    container-id...: %s\n\n", substr($id, 0, 12)));

        # TODO: different deployments, mount (default), mount-ro, copy
        if (null !== $result = $this->deployCopy($copy, $id, $this->directories->getProject())) {
            return $result;
        }

        $status = $this->runStepScript($step, $streams, $exec, $name);

        $this->captureStepArtifacts($step, $copy && 0 === $status, $id, $dir);

        $this->shutdownStepContainer($status, $id, $exec, $name);

        return $status;
    }

    /**
     * @param string $name
     */
    private function zapContinerByName($name)
    {
        $ids = null;

        $status = $this->exec->capture(
            'docker',
            array(
                'ps', '-qa', '--filter',
                "name=^/${name}$"
            ),
            $result
        );

        $status || $ids = Lib::lines($result);

        if ($status || !(is_array($ids) && 1 === count($ids))) {
            return;
        }

        $this->exec->capture('docker', Lib::merge('kill', $ids));
        $this->exec->capture('docker', Lib::merge('rm', $ids));
    }

    /**
     * @param Image $image
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
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
     * @throws \RuntimeException
     * @return null|int null if all clear, integer for exit status
     */
    private function deployCopy($copy, $id, $dir)
    {
        if (!$copy) {
            return null;
        }

        $streams = $this->streams;
        $exec = $this->exec;

        $streams->out("\x1D+++ copying files into container...\n");

        $tmpDir = sys_get_temp_dir() . '/pipelines/cp';
        Lib::fsMkdir($tmpDir);
        Lib::fsSymlink($dir, $tmpDir . '/app');
        $cd = Lib::cmd('cd', array($tmpDir . '/.'));
        $tar = Lib::cmd(
            'tar',
            array(
                'c', '-h', '-f', '-', '--no-recursion', 'app')
        );
        $dockerCp = Lib::cmd(
            'docker ',
            array(
                'cp', '-', $id . ':/.')
        );
        $status = $exec->pass("${cd} && echo 'app' | ${tar} | ${dockerCp}", array());
        Lib::fsUnlink($tmpDir . '/app');
        if (0 !== $status) {
            $streams->err('pipelines: deploy copy failure\n');

            return $status;
        }

        $cd = Lib::cmd('cd', array($dir . '/.'));
        $tar = Lib::cmd(
            'tar',
            array(
                'c', '-f', '-', '.')
        );
        $dockerCp = Lib::cmd(
            'docker ',
            array(
                'cp', '-', $id . ':/app')
        );
        $status = $exec->pass("${cd} && ${tar} | ${dockerCp}", array());
        if (0 !== $status) {
            $streams->err('pipelines: deploy copy failure\n');

            return $status;
        }

        $streams('');

        return null;
    }

    /**
     * @param Step $step
     * @param Streams $streams
     * @param Exec $exec
     * @param string $name container name
     * @return null|int should never be null, status, non-zero if a command failed
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
            if (0 !== $status) {
                $this->streams->err(sprintf("script non-zero exit status: %d\n", $status));

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
     * @throws \RuntimeException
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
            $this->captureArtifactPattern($source, $pattern, $dir);
        }

        $streams("");
    }

    /**
     * @see Runner::captureStepArtifacts()
     *
     * @param ArtifactSource $source
     * @param string $pattern
     * @param string $dir
     * @throws \RuntimeException
     */
    private function captureArtifactPattern(ArtifactSource $source, $pattern, $dir)
    {
        $exec = $this->exec;
        $streams = $this->streams;

        $id = $source->getId();
        $paths = $source->findByPattern($pattern);
        if (empty($paths)) {
            return;
        }

        $chunkSize = 1792;
        $chunks = array_chunk($paths, $chunkSize, true);

        foreach ($chunks as $paths) {
            $docker = Lib::cmd('docker', array('exec', '-w', '/app', $id));
            $tar = Lib::cmd('tar', array('c', '-f', '-', $paths));
            $unTar = Lib::cmd('tar', array('x', '-f', '-', '-C', $dir));

            $status = $exec->pass($docker . ' ' . $tar . ' | ' . $unTar, array());

            if (0 !== $status) {
                $streams->err(
                    sprintf("pipelines: Artifact failure: '%s' (%d, %d paths)\n", $pattern, $status, count($paths))
                );
            }
        }
    }

    /**
     * @param int $status
     * @param string $id container id
     * @param Exec $exec
     * @param string $name container name
     * @throws \RuntimeException
     */
    private function shutdownStepContainer($status, $id, Exec $exec, $name)
    {
        $flags = $this->flags;

        # keep container on error
        if (0 !== $status && $flags & self::FLAG_KEEP_ON_ERROR) {
            $this->streams->err(sprintf(
                "keeping container id %s\n",
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

    /**
     * @param Step $step
     * @return string
     */
    private function generateContainerName(Step $step)
    {
        $project = $this->directories->getName();
        $idContainerSlug = preg_replace('([^a-zA-Z0-9_.-]+)', '', $step->getPipeline()->getId());
        if ('' === $idContainerSlug) {
            $idContainerSlug = 'null';
        }
        $nameSlug = preg_replace(array('( )', '([^a-zA-Z0-9_.-]+)'), array('-', ''), $step->getName());
        if ('' === $nameSlug) {
            $nameSlug = 'no-name';
        }

        return $this->prefix . '-' . implode(
                '.',
                array_reverse(
                array(
                    $project,
                    $idContainerSlug,
                    $nameSlug,
                    $step->getIndex() + 1,
                )
            )
        );
    }
}
