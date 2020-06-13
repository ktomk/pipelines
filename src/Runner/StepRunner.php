<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\DestructibleString;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibFsPath;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Runner\Containers\StepContainer;
use Ktomk\Pipelines\Runner\Docker\ArtifactSource;
use Ktomk\Pipelines\Runner\Docker\Binary\Repository;
use Ktomk\Pipelines\Runner\Docker\ImageLogin;

/**
 * Runner for a single step of a pipeline
 */
class StepRunner
{
    /**
     * list of temporary directory destructible markers
     *
     * @var array
     */
    private $temporaryDirectories = array();

    /**
     * @var Runner
     */
    private $runner;

    /**
     * DockerSession constructor.
     *
     * @param Runner $runner
     */
    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * @param Step $step
     *
     * @return null|int exist status of step script or null if the run operation failed
     */
    public function runStep(Step $step)
    {
        $dir = $this->runner->getDirectories()->getProjectDirectory();
        $env = $this->runner->getEnv();
        $streams = $this->runner->getStreams();

        $containers = new Containers($this->runner);

        $env->setPipelinesProjectPath($dir);

        $container = $containers->createStepContainer($step);

        $env->setContainerName($container->getName());

        $image = $step->getImage();

        # launch container
        $streams->out(sprintf(
            "\x1D+++ step #%d\n\n    name...........: %s\n    effective-image: %s\n    container......: %s\n",
            $step->getIndex() + 1,
            $step->getName() ? '"' . $step->getName() . '"' : '(unnamed)',
            $image->getName(),
            $container->getName()
        ));

        $id = $container->keepOrKill();

        $deployCopy = $this->runner->getFlags()->deployCopy();

        if (null === $id) {
            list($id, $status) = $this->runNewContainer($container, $dir, $deployCopy, $step);
            if (null === $id) {
                return $status;
            }
        }

        $streams->out(sprintf("    container-id...: %s\n\n", substr($id, 0, 12)));

        # TODO: different deployments, mount (default), mount-ro, copy
        if (null !== $result = $this->deployCopy($deployCopy, $id, $dir)) {
            return $result;
        }

        $status = StepScriptRunner::createRunStepScript($this->runner, $container->getName(), $step);

        $this->captureStepArtifacts($step, $deployCopy && 0 === $status, $id, $dir);

        $container->shutdown($status);

        return $status;
    }

    /**
     * method to wrap new to have a test-point
     *
     * @return Repository
     */
    public function getDockerBinaryRepository()
    {
        $repo = Repository::create($this->runner->getExec(), $this->runner->getDirectories());
        $repo->resolve($this->runner->getRunOpts()->getBinaryPackage());

        return $repo;
    }

    /**
     * @param Step $step
     * @param bool $copy
     * @param string $id container id
     * @param string $dir to put artifacts in (project directory)
     *
     * @throws \RuntimeException
     *
     * @return void
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

        $exec = $this->runner->getExec();
        $streams = $this->runner->getStreams();

        $streams->out("\x1D+++ copying artifacts from container...\n");

        $source = new ArtifactSource($exec, $id, $dir);

        $patterns = $artifacts->getPatterns();
        foreach ($patterns as $pattern) {
            $this->captureArtifactPattern($source, $pattern, $dir);
        }

        $streams('');
    }

    /**
     * capture artifact pattern
     *
     * @param ArtifactSource $source
     * @param string $pattern
     * @param string $dir
     *
     * @throws \RuntimeException
     *
     * @return void
     *
     * @see Runner::captureStepArtifacts()
     *
     */
    private function captureArtifactPattern(ArtifactSource $source, $pattern, $dir)
    {
        $exec = $this->runner->getExec();
        $streams = $this->runner->getStreams();

        $id = $source->getId();
        $paths = $source->findByPattern($pattern);
        if (empty($paths)) {
            return;
        }

        $chunks = Lib::arrayChunkByStringLength($paths, 131072, 4);

        foreach ($chunks as $paths) {
            $docker = Lib::cmd('docker', array('exec', '-w', '/app', $id));
            $tar = Lib::cmd('tar', array('c', '-f', '-', $paths));
            $unTar = Lib::cmd('tar', array('x', '-f', '-', '-C', $dir));

            $command = $docker . ' ' . $tar . ' | ' . $unTar;
            $status = $exec->pass($command, array());

            if (0 !== $status) {
                $streams->err(sprintf(
                    "pipelines: Artifact failure: '%s' (%d, %d paths, %d bytes)\n",
                    $pattern,
                    $status,
                    count($paths),
                    strlen($command)
                ));
            }
        }
    }

    /**
     * @param bool $copy
     * @param string $id container id
     * @param string $dir directory to copy contents into container
     *
     * @throws \RuntimeException
     *
     * @return null|int null if all clear, integer for exit status
     */
    private function deployCopy($copy, $id, $dir)
    {
        if (!$copy) {
            return null;
        }

        $streams = $this->runner->getStreams();
        $exec = $this->runner->getExec();

        $streams->out("\x1D+++ copying files into container...\n");

        $tmpDir = LibTmp::tmpDir('pipelines-cp.');
        $this->temporaryDirectories[] = DestructibleString::rmDir($tmpDir);
        LibFs::symlink($dir, $tmpDir . '/app');
        $cd = Lib::cmd('cd', array($tmpDir . '/.'));
        $tar = Lib::cmd('tar', array('c', '-h', '-f', '-', '--no-recursion', 'app'));
        $dockerCp = Lib::cmd('docker ', array('cp', '-', $id . ':/.'));
        $status = $exec->pass("${cd} && echo 'app' | ${tar} | ${dockerCp}", array());
        LibFs::unlink($tmpDir . '/app');
        if (0 !== $status) {
            $streams->err('pipelines: deploy copy failure\n');

            return $status;
        }

        $cd = Lib::cmd('cd', array($dir . '/.'));
        $tar = Lib::cmd('tar', array('c', '-f', '-', '.'));
        $dockerCp = Lib::cmd('docker ', array('cp', '-', $id . ':/app'));
        $status = $exec->pass("${cd} && ${tar} | ${dockerCp}", array());
        if (0 !== $status) {
            $streams->err('pipelines: deploy copy failure\n');

            return $status;
        }

        $streams('');

        return null;
    }

    /**
     * @param StepContainer $container
     * @param string $dir
     * @param bool $copy
     * @param Step $step
     *
     * @return array array(string|null $id, int $status)
     */
    private function runNewContainer(StepContainer $container, $dir, $copy, Step $step)
    {
        $env = $this->runner->getEnv();
        $streams = $this->runner->getStreams();

        $mountDockerSock = $this->obtainDockerSocketMount();

        $mountDockerClient = $this->obtainDockerClientMount($step);

        $mountWorkingDirectory = $this->obtainWorkingDirMount($copy, $dir, $mountDockerSock);
        if ($mountWorkingDirectory && is_int($mountWorkingDirectory[1])) {
            return $mountWorkingDirectory;
        }

        $network = $container->getServiceContainers()->obtainNetwork();

        # process docker login if image demands so, but continue on failure
        $image = $step->getImage();
        ImageLogin::loginImage($this->runner->getExec(), $this->runner->getEnv()->getResolver(), null, $image);

        list($status, $out, $err) = $container->run(
            array(
                $network,
                '-i', '--name', $container->getName(),
                $env->getArgs('-e'),
                $env::createArgVarDefinitions('-e', $step->getEnv()),
                $mountWorkingDirectory, '-e', 'BITBUCKET_CLONE_DIR=/app',
                $mountDockerSock,
                $mountDockerClient,
                '--workdir', '/app', '--detach', '--entrypoint=/bin/sh', $image->getName(),
            )
        );
        if (0 !== $status) {
            $streams->out("    container-id...: *failure*\n\n");
            $streams->err("pipelines: setting up the container failed\n");
            $streams->err("${err}\n");
            $streams->out("${out}\n");
            $streams->out(sprintf("exit status: %d\n", $status));

            return array(null, $status);
        }
        $id = $container->getDisplayId();

        return array($id, $status);
    }

    /**
     * @param Step $step
     *
     * @return string[]
     */
    private function obtainDockerClientMount(Step $step)
    {
        if (!$step->getServices()->has('docker')) {
            return array();
        }

        $path = $this->runner->getRunOpts()->getOption('docker.client.path');

        // prefer pip mount over package
        $hostPath = $this->pipHostConfigBind($path);
        if (null !== $hostPath) {
            return array('-v', sprintf('%s:%s:ro', $hostPath, $path));
        }

        $local = $this->getDockerBinaryRepository()->getBinaryPath();
        chmod($local, 0755);

        return array('-v', sprintf('%s:%s:ro', $local, $path));
    }

    /**
     * enable docker client inside docker by mounting docker socket
     *
     * @return array docker socket volume args for docker run, empty if not mounting
     */
    private function obtainDockerSocketMount()
    {
        $args = array();

        // FIXME give more controlling options, this is serious /!\
        if (!$this->runner->getFlags()->useDockerSocket()) {
            return $args;
        }

        $defaultSocketPath = $this->runner->getRunOpts()->getOption('docker.socket.path');
        $hostPathDockerSocket = $defaultSocketPath;

        // pipelines inside pipelines
        $hostPath = $this->pipHostConfigBind($defaultSocketPath);
        if (null !== $hostPath) {
            return array(
                '-v', sprintf('%s:%s', $hostPath, $defaultSocketPath),
            );
        }

        $dockerHost = $this->runner->getEnv()->getInheritValue('DOCKER_HOST');
        if (null !== $dockerHost && 0 === strpos($dockerHost, 'unix://')) {
            $hostPathDockerSocket = LibFsPath::normalize(substr($dockerHost, 7));
        }

        $pathDockerSock = $defaultSocketPath;

        if (file_exists($hostPathDockerSocket)) {
            $args = array(
                '-v', sprintf('%s:%s', $hostPathDockerSocket, $pathDockerSock),
            );
        }

        return $args;
    }

    /**
     * @param bool $copy
     * @param string $dir
     * @param array $mountDockerSock
     *
     * @return array mount options or array(null, int $status) for error handling
     */
    private function obtainWorkingDirMount($copy, $dir, array $mountDockerSock)
    {
        if ($copy) {
            return array();
        }

        $parentName = $this->runner->getEnv()->getValue('PIPELINES_PARENT_CONTAINER_NAME');
        $hostDeviceDir = $this->pipHostConfigBind($dir);
        $checkMount = $mountDockerSock && null !== $parentName;
        $deviceDir = $hostDeviceDir ?: $dir;
        if ($checkMount && '/app' === $dir && null === $hostDeviceDir) { // FIXME(tk): hard encoded /app
            $deviceDir = $this->runner->getEnv()->getValue('PIPELINES_PROJECT_PATH');
            if ($deviceDir === $dir || null === $deviceDir) {
                $this->runner->getStreams()->err("pipelines: fatal: can not detect ${dir} mount point. preventing new container.\n");

                return array(null, 1);
            }
        }

        // FIXME(tk): Never mount anything not matching /home/[a-zA-Z][a-zA-Z0-9]*/[^.].*/...
        //   + do realpath checking
        //   + prevent dot path injections (logical fix first)
        return array('-v', "${deviceDir}:/app"); // FIXME(tk): hard encoded /app
    }

    /**
     * get host path from mount point if in pip level 2+
     *
     * @param mixed $mountPoint
     *
     * @return null|string
     */
    private function pipHostConfigBind($mountPoint)
    {
        // if there is a parent name, this is level 2+
        if (null === $pipName = $this->runner->getEnv()->getValue('PIPELINES_PIP_CONTAINER_NAME')) {
            return null;
        }

        return Docker::create($this->runner->getExec())->hostConfigBind($pipName, $mountPoint);
    }
}
