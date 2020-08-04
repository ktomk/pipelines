<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\Runner\Containers;
use Ktomk\Pipelines\Runner\Directories;
use Ktomk\Pipelines\Runner\Env;
use Ktomk\Pipelines\Runner\RunOpts;

/**
 * Class ServiceOptions
 *
 * --run-service <service> - run a service (attached) and exit after service exited
 *
 * for trouble-shooting/integrating a service quickly
 *
 * @package Ktomk\Pipelines\Utility
 */
class ServiceOptions implements Runnable
{
    /**
     * @var Args
     */
    private $args;

    /**
     * @var Streams
     */
    private $streams;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var Env
     */
    private $env;

    /**
     * @var RunOpts
     */
    private $runOpts;

    /**
     * @var Directories
     */
    private $directories;

    /**
     * @param Args $args
     * @param Streams $streams
     * @param File $file
     * @param Exec $exec
     * @param Env $env
     * @param RunOpts $runOpts
     * @param Directories $directories
     *
     * @return ServiceOptions
     */
    public static function bind(
        Args $args,
        Streams $streams,
        File $file,
        Exec $exec,
        Env $env,
        RunOpts $runOpts,
        Directories $directories
    ) {
        return new self($args, $streams, $file, $exec, $env, $runOpts, $directories);
    }

    /**
     * ServiceOptions constructor.
     *
     * @param Args $args
     * @param Streams $streams
     * @param File $file
     * @param Exec $exec
     * @param Env $env
     * @param RunOpts $runOpts
     * @param Directories $directories
     */
    public function __construct(
        Args $args,
        Streams $streams,
        File $file,
        Exec $exec,
        Env $env,
        RunOpts $runOpts,
        Directories $directories
    ) {
        $this->args = $args;
        $this->streams = $streams;
        $this->file = $file;
        $this->exec = $exec;
        $this->env = $env;
        $this->runOpts = $runOpts;
        $this->directories = $directories;
    }

    /**
     * @throws ArgsException
     * @throws StatusException
     *
     * @return void
     */
    public function run()
    {
        if ('' === $nameOfService = $this->args->getStringOptionArgument('service', '')) {
            return;
        }

        $service = $this->file->getDefinitions()->getServices()->getByName($nameOfService);
        if (null === $service) {
            throw new ArgsException(sprintf('undefined service: %s', $nameOfService), 1);
        }

        $this->streams->out(sprintf("starting service %s ...\n", $nameOfService));

        $project = $this->env->getValue('BITBUCKET_REPO_SLUG') ?: $this->directories->getName();

        $labels = new Containers\LabelsBuilder();
        $labels
            ->setPrefix($this->runOpts->getPrefix())
            ->setProject($project)
            ->setProjectDirectory($this->directories->getProjectDirectory())
            ;

        list($status) = Containers::execRunServiceContainerAttached(
            $this->exec,
            $service,
            $this->env->getResolver(),
            $this->runOpts->getPrefix(),
            $project,
            $labels->setRole('service')->toArray()
        );

        throw new StatusException('', $status);
    }
}
