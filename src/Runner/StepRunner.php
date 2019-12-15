<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\DestructibleString;
use Ktomk\Pipelines\File\Image;
use Ktomk\Pipelines\File\Step;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibTmp;

/**
 * Runner for a single step of a pipeline
 */
class StepRunner
{
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
     * @var Flags
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
     * list of temporary directory destructible markers
     *
     * @var array
     */
    private $temporaryDirectories = array();

    /**
     * DockerSession constructor.
     *
     * @param string $prefix
     * @param Directories $directories source repository root directory based directories object
     * @param Exec $exec
     * @param Flags $flags
     * @param Env $env
     * @param Streams $streams
     */
    public function __construct(
        $prefix,
        Directories $directories,
        Exec $exec,
        Flags $flags,
        Env $env,
        Streams $streams
    )
    {
        $this->prefix = $prefix;
        $this->directories = $directories;
        $this->exec = $exec;
        $this->flags = $flags;
        $this->env = $env;
        $this->streams = $streams;
    }

    /**
     * @param Step $step
     * @return null|int exist status of step script or null if the run operation failed
     */
    public function runStep(Step $step)
    {
        $dir = $this->directories->getProjectDirectory();
        $env = $this->env;
        $exec = $this->exec;
        $streams = $this->streams;
        $reuseContainer = $this->flags->reuseContainer();
        $deployCopy = $this->flags->deployCopy();

        $name = $this->generateContainerName($step);

        if (false === $reuseContainer) {
            $this->zapContainerByName($name);
        }
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

        $id = null;
        if ($reuseContainer) {
            $id = $this->dockerGetContainerIdByName($name);
        }

        if (null === $id) {
            list($id, $status) = $this->runNewContainer($name, $dir, $deployCopy, $step);
            if (null === $id) {
                return $status;
            }
        }

        $streams->out(sprintf("    container-id...: %s\n\n", substr($id, 0, 12)));

        # TODO: different deployments, mount (default), mount-ro, copy
        if (null !== $result = $this->deployCopy($deployCopy, $id, $dir)) {
            return $result;
        }

        $status = $this->runStepScript($step, $streams, $exec, $name);

        $this->captureStepArtifacts($step, $deployCopy && 0 === $status, $id, $dir);

        $this->shutdownStepContainer($status, $id, $exec, $name);

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

        $streams('');
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
     * @param string $name
     * @return null|string
     */
    private function dockerGetContainerIdByName($name)
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
            return null;
        }

        return $ids[0];
    }

    /**
     * @param Step $step
     * @return string
     */
    private function generateContainerName(Step $step)
    {
        $project = $this->directories->getName();
        $idContainerSlug = preg_replace('([^a-zA-Z0-9_.-]+)', '-', $step->getPipeline()->getId());
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
                        trim($idContainerSlug, '-'),
                        $nameSlug,
                        $step->getIndex() + 1,
                    )
            )
        );
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
     * @param string $name
     * @param string $dir
     * @param bool $copy
     * @param Step $step
     * @return array array(string|null $id, int $status)
     */
    private function runNewContainer($name, $dir, $copy, Step $step)
    {
        $env = $this->env;
        $exec = $this->exec;
        $streams = $this->streams;

        $image = $step->getImage();

        # process docker login if image demands so, but continue on failure
        $this->imageLogin($image);

        // enable docker client inside docker by mounting docker socket
        // FIXME give controlling options, this is serious /!\
        $mountDockerSock = array();
        if ($this->flags->useDockerSocket() && file_exists('/var/run/docker.sock')) {
            $mountDockerSock = array(
                '-v', '/var/run/docker.sock:/var/run/docker.sock',
            );
        }

        $parentName = $env->getValue('PIPELINES_PARENT_CONTAINER_NAME');
        $checkMount = $mountDockerSock && null !== $parentName;
        $deviceDir = $dir;
        if ($checkMount) {
            $docker = new Docker($exec);
            $deviceDir = $docker->hostDevice($parentName, $dir);
            unset($docker);
        }

        $mountWorkingDirectory = $copy
            ? array()
            : array('--volume', "${deviceDir}:/app"); // FIXME(tk): hard encoded /app

        $status = $exec->capture('docker', array(
            'run', '-i', '--name', $name,
            $env->getArgs('-e'),
            $mountWorkingDirectory, '-e', 'BITBUCKET_CLONE_DIR=/app',
            $mountDockerSock,
            '--workdir', '/app', '--detach', '--entrypoint=/bin/sh', $image->getName()
        ), $out, $err);
        if (0 !== $status) {
            $streams->out("    container-id...: *failure*\n\n");
            $streams->err("pipelines: setting up the container failed\n");
            $streams->err("${err}\n");
            $streams->out("${out}\n");
            $streams->out(sprintf("exit status: %d\n", $status));

            return array(null, $status);
        }
        $id = rtrim($out) ?: '*dry-run*'; # side-effect: internal exploit of no output with true exit status

        return array($id, 0);
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

        $buffer = Lib::cmd("<<'SCRIPT' docker", array(
            'exec', '-i', $name, '/bin/sh'
        ));
        $buffer .= "\n# this /bin/sh script is generated from a pipelines pipeline:\n";
        foreach ($script as $line => $command) {
            $buffer .= 'printf \'\\035+ %s\\n\' ' . Lib::quoteArg($command) . "\n";
            $buffer .= $command . "\n";
            $buffer .= 'ret=$?' . "\n";
            $buffer .= 'printf \'\\n\'' . "\n";
            $buffer .= 'if [ $ret -ne 0 ]; then exit $ret; fi' . "\n";
        }
        $buffer .= "SCRIPT\n";

        $status = $exec->pass($buffer, array());

        if (0 !== $status) {
            $streams->err(sprintf("script non-zero exit status: %d\n", $status));
        }

        return $status;
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
        if (0 !== $status && $flags->keepOnError()) {
            $this->streams->err(sprintf(
                "error, keeping container id %s\n",
                substr($id, 0, 12)
            ));

            return;
        }

        # keep or remove container
        if ($flags->killContainer()) {
            $exec->capture('docker', array('kill', $name));
        }

        if ($flags->removeContainer()) {
            $exec->capture('docker', array('rm', $name));
        }

        if ($flags->keep()) {
            $this->streams->out(sprintf(
                "keeping container id %s\n",
                substr($id, 0, 12)
            ));
        }
    }

    /**
     * @param string $name
     */
    private function zapContainerByName($name)
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
}
