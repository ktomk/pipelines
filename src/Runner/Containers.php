<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\Definitions\Service;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\Runner\Containers\NameBuilder;
use Ktomk\Pipelines\Runner\Containers\StepContainer;
use Ktomk\Pipelines\Runner\Docker\ArgsBuilder;
use Ktomk\Pipelines\Runner\Docker\ImageLogin;

/**
 * Class Containers
 *
 * Containers as per the runner
 *
 * An extraction of more generic container functionality from the
 * StepContainer that is more specific and did host them since
 * introducing services
 *
 * @package Ktomk\Pipelines\Runner
 */
class Containers
{
    /**
     * @var Runner
     */
    private $runner;

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
     * Run service container
     *
     * @param Exec $exec
     * @param Service $service
     * @param callable $resolver
     * @param string $prefix
     * @param string $project
     * @param array $labels
     *
     * @return array
     * @psalm-return array{0: int, 1: array}
     */
    public static function execRunServiceContainer(Exec $exec, Service $service, $resolver, $prefix, $project, array $labels)
    {
        $execRunServiceRunner = self::execRunServiceContainerImpl($exec, $service, $resolver, $prefix, $project);

        return $execRunServiceRunner(false, Lib::merge('--detach', ArgsBuilder::optMap('-l', $labels)));
    }

    /**
     * Run service container attached
     *
     * @param Exec $exec
     * @param Service $service
     * @param callable $resolver
     * @param string $prefix
     * @param string $project
     * @param array $labels
     *
     * @return array
     */
    public static function execRunServiceContainerAttached(Exec $exec, Service $service, $resolver, $prefix, $project, array $labels)
    {
        $execRunServiceRunner = self::execRunServiceContainerImpl($exec, $service, $resolver, $prefix, $project);

        return $execRunServiceRunner(true, Lib::merge('--rm', ArgsBuilder::optMap('-l', $labels)));
    }

    /**
     * @param Exec $exec
     * @param Service $service
     * @param callable $resolver
     * @param string $prefix
     * @param string $project
     *
     * @return \Closure
     */
    public static function execRunServiceContainerImpl(Exec $exec, Service $service, $resolver, $prefix, $project)
    {
        /**
         * @param bool $pass (or capture)
         * @param string|string[] $additionalArgs
         *
         * @return array
         */
        return function ($pass, $additionalArgs) use ($exec, $service, $resolver, $prefix, $project) {
            $network = array('--network', 'host');
            $image = $service->getImage();
            ImageLogin::loginImage($exec, $resolver, null, $image);

            $containerName = NameBuilder::serviceContainerName($prefix, $service->getName(), $project);

            $variables = $resolver($service->getVariables());

            $args = Lib::merge(
                $network,
                '--name',
                $containerName,
                $additionalArgs,
                ArgsBuilder::optMap('-e', $variables, true),
                $image->getName()
            );

            $status = $pass
                ? $exec->pass('docker', Lib::merge('run', $args))
                : $exec->capture('docker', Lib::merge('run', $args), $out, $err);

            return array($status, $network);
        };
    }

    /**
     * @param Exec $exec
     * @param Streams $streams
     * @param string|string[] $idOrIds
     * @param int $status
     * @param Flags $flags
     * @param string $message
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
     * StepContainers constructor.
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
     * @return StepContainer
     */
    public function createStepContainer(Step $step)
    {
        $prefix = $this->runner->getPrefix();
        $projectName = $this->runner->getProject();

        $name = NameBuilder::stepContainerNameByStep($step, $prefix, $projectName);

        return new StepContainer($name, $step, $this->runner);
    }
}
