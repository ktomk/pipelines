<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\Definitions\Service;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\Runner\Docker\ImageLogin;

/**
 * Class StepContainer
 *
 * @package Ktomk\Pipelines\Runner
 */
class StepContainer
{
    /**
     * @var null|string id of the (running) container
     */
    private $id;

    /**
     * @var null|string name of the container
     */
    private $name;

    /**
     * @var Step
     */
    private $step;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @param Step $step
     * @param null|Exec $exec
     *
     * @return StepContainer
     */
    public static function create(Step $step, Exec $exec = null)
    {
        if (null === $exec) {
            $exec = new Exec();
        }

        return new self($step, $exec);
    }

    /**
     * @param Step $step
     * @param string $prefix
     * @param string $project name
     *
     * @return string
     */
    public static function createName(Step $step, $prefix, $project)
    {
        return self::create($step)->generateName($prefix, $project);
    }

    /**
     * kill and remove static implementation
     *
     * @param Exec $exec
     * @param string|string[] $idOrIds container id(s) or name(s)
     * @param bool $kill
     * @param bool $remove
     *
     * @return void
     */
    public static function execKillAndRemove(Exec $exec, $idOrIds, $kill, $remove)
    {
        if ($kill) {
            Docker::create($exec)->getProcessManager()->kill($idOrIds);
        }

        if ($remove) {
            Docker::create($exec)->getProcessManager()->remove($idOrIds);
        }
    }

    /**
     * @param Exec $exec
     * @param array $args
     *
     * @return array array(int $status, string $out, string $err, string|null $id)
     */
    public static function execRun(Exec $exec, array $args)
    {
        $status = $exec->capture('docker', Lib::merge('run', $args), $out, $err);

        $id = null;
        if (0 === $status) {
            $id = rtrim($out) ?: null;
        }

        return array($status, $out, $err, $id);
    }

    /**
     * @param Exec $exec
     * @param Streams $streams
     * @param string|string[] $idOrIds
     * @param int $status
     * @param Flags $flags
     * @param string $message
     * @param string $id
     *
     * @return void
     *
     * @see StepRunner::shutdownStepContainer
     */
    public static function execShutdownContainer(Exec $exec, Streams $streams, $idOrIds, $status, Flags $flags, $message)
    {
        # keep container on error
        if (0 !== $status && $flags->keepOnError()) {
            $streams->err(sprintf("error, %s\n", $message));

            return;
        }

        # keep or kill/remove container
        self::execKillAndRemove($exec, $idOrIds, $flags->killContainer(), $flags->removeContainer());

        if ($flags->keep()) {
            $streams->out(sprintf("%s\n", $message));
        }
    }

    /**
     * generate step container name
     *
     * example: pipelines-1.pipeline-features-and-introspection.default.app
     *              ^    `^`                  ^                `    ^  ` ^
     *              |     |                   |                     |    |
     *              | step number        step name           pipeline id |
     *           prefix                                                project
     *
     * @param string $prefix
     * @param Step $step
     * @param string $projectName
     *
     * @return string
     *
     * @see StepContainer::generateName()
     */
    public static function generateStepName($prefix, Step $step, $projectName)
    {
        $idContainerSlug = preg_replace('([^a-zA-Z0-9_.-]+)', '-', $step->getPipeline()->getId());
        if ('' === $idContainerSlug) {
            $idContainerSlug = 'null';
        }
        $nameSlug = preg_replace(array('( )', '([^a-zA-Z0-9_.-]+)'), array('-', ''), $step->getName());
        if ('' === $nameSlug) {
            $nameSlug = 'no-name';
        }

        $stepNumber = $step->getIndex() + 1;

        return $prefix . '-' . implode(
            '.',
            array(
                    $stepNumber,
                    $nameSlug,
                    trim($idContainerSlug, '-'),
                    $projectName,
                )
        );
    }

    /**
     * generate service container name
     *
     * example: pipelines-service-redis.pipelines
     *              ^    `   ^   `  ^  `   ^
     *              |        |      |      |
     *              |    "service"  |   project
     *           prefix       service name
     *
     * @param string $prefix
     * @param string $serviceName
     * @param string $project
     *
     * @return string
     *
     * @see StepRunner::obtainServicesNetwork()
     */
    public static function generateServiceName($prefix, $serviceName, $project)
    {
        $nameSlug = preg_replace(array('( )', '([^a-zA-Z0-9_.-]+)'), array('-', ''), $serviceName);

        return $prefix . '-service-' . implode(
            '.',
            array(
                    $nameSlug,
                    $project,
                )
        );
    }

    /**
     * @param Exec $exec
     * @param Service $service
     * @param callable $resolver
     * @param string $prefix
     * @param string $project
     *
     * @return array
     */
    public static function execRunServiceContainer(Exec $exec, Service $service, $resolver, $prefix, $project)
    {
        $network = array('--network', 'host');
        $image = $service->getImage();
        ImageLogin::loginImage($exec, $resolver, null, $image);

        $containerName = self::generateServiceName($prefix, $service->getName(), $project);

        $variables = $resolver($service->getVariables());

        $args = array(
            $network, '--name',
            $containerName,
            '--detach',
            Env::createArgVarDefinitions('-e', $variables),
            $image->getName(),
        );

        $status = $exec->capture('docker', Lib::merge('run', $args), $out, $err);

        return array($status, $network);
    }

    /**
     * @param Exec $exec
     * @param Service $service
     * @param callable $resolver
     * @param string $prefix
     * @param string $project
     *
     * @return array
     */
    public static function execRunServiceContainerAttached(Exec $exec, Service $service, $resolver, $prefix, $project)
    {
        $network = array('--network', 'host');
        $image = $service->getImage();
        ImageLogin::loginImage($exec, $resolver, null, $image);

        $containerName = self::generateServiceName($prefix, $service->getName(), $project);

        $variables = $resolver($service->getVariables());

        $args = array(
            $network, '--name',
            $containerName,
            '--rm',
            Env::createArgVarDefinitions('-e', $variables),
            $image->getName(),
        );

        $status = $exec->pass('docker', Lib::merge('run', $args));

        return array($status, $network);
    }

    /**
     * StepContainer constructor.
     *
     * @param Step $step
     * @param Exec $exec
     */
    public function __construct(Step $step, Exec $exec)
    {
        $this->step = $step;
        $this->exec = $exec;
    }

    /**
     * generate step container name
     *
     * @param string $prefix for the name (normally "pipelines")
     * @param string $project name
     *
     * @return string
     */
    public function generateName($prefix, $project)
    {
        return $this->name = self::generateStepName($prefix, $this->step, $project);
    }

    /**
     * the display id
     *
     *   side-effect: if id is null, this signals a dry-run which is made
     * visible by the string "*dry-run*"
     *
     * @return string
     */
    public function getDisplayId()
    {
        return isset($this->id) ? $this->id : '*dry-run*';
    }

    /**
     * @return null|string ID of (once) running container or null if not yet running
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|string name of the container, NULL if no name generated yet
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $keep a container on true, kill on false (if it exists)
     *
     * @return null|string
     */
    public function keepOrKill($keep)
    {
        $name = $this->name;
        if (null === $name) {
            throw new \BadMethodCallException('Container has no name yet');
        }

        $processManager = Docker::create($this->exec)->getProcessManager();

        if (false === $keep) {
            $processManager->zapContainersByName($name);

            return $this->id = null;
        }

        return $this->id = $processManager->findContainerIdByName($name);
    }

    /**
     * @param bool $kill
     * @param bool $remove
     *
     * @return void
     */
    public function killAndRemove($kill, $remove)
    {
        $id = $this->getDisplayId();

        self::execKillAndRemove($this->exec, $id, $kill, $remove);
    }

    /**
     * @param array $args
     *
     * @return array array(int $status, string $out, string $err)
     */
    public function run(array $args)
    {
        $execRun = self::execRun($this->exec, $args);
        $this->id = array_pop($execRun);

        return $execRun;
    }
}
