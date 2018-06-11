<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\DockerProcessManager;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\Lib;
use RuntimeException;

/**
 * docker specific options of the pipelines utility
 *
 * --docker-list
 * --docker-kill
 * --docker-clean
 *
 * @package Ktomk\Pipelines\Utility
 */
class DockerOptions
{
    /**
     * @var Streams
     */
    private $streams;

    /**
     * @var Args
     */
    private $args;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var DockerProcessManager
     */
    private $ps;

    /**
     * DockerOptions constructor.
     *
     * @param Args $args
     * @param Exec $exec
     * @param string $prefix
     * @param Streams $streams
     */
    public function __construct(Args $args, Exec $exec, $prefix, Streams $streams)
    {
        $this->streams = $streams;
        $this->args = $args;
        $this->exec = $exec;
        $this->ps = new DockerProcessManager($exec);
        $this->prefix = $prefix;
    }

    public static function bind(Args $args, Exec $exec, $prefix, Streams $streams)
    {
        return new self($args, $exec, $prefix, $streams);
    }

    /**
     * Process docker related options
     *
     * --docker-list  - list containers
     * --docker-kill  - kill (running) containers
     * --docker-clean - remove (stopped) containers
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws StatusException
     */
    public function run()
    {
        $args = $this->args;

        $count = 0;
        $status = 0;

        if (!$status && $args->hasOption('docker-list')) {
            $count++;
            $status = $this->runPs();
        }

        $hasKill = $args->hasOption('docker-kill');
        $hasClean = $args->hasOption('docker-clean');
        if ($args->hasOption('docker-zap')) {
            $hasKill = $hasClean = true;
        }

        $ids = null;
        if ($hasClean || $hasKill) {
            $count++;
            $ids = $this->ps->findAllContainerIdsByNamePrefix($this->prefix . '-');
        }

        if (!$status && $hasKill) {
            $count++;
            $running = $this->ps->findRunningContainerIdsByNamePrefix($this->prefix . '-');
            if ($running) {
                $status = $this->ps->kill($running);
            } else {
                $this->info('no containers to kill');
            }
        }

        if (!$status && $hasClean) {
            $count++;
            if ($ids) {
                $status = $this->ps->remove($ids);
            } else {
                $this->info('no containers to remove');
            }
        }

        if ($count) {
            StatusException::status($status);
        }
    }

    private function info($message)
    {
        $this->streams->out(
            sprintf("%s\n", $message)
        );
    }

    /**
     * @return int
     */
    private function runPs()
    {
        $exec = $this->exec;
        $prefix = $this->prefix;

        $status = $exec->pass(
            'docker ps -a',
            array(
                '--filter',
                "name=^/${prefix}-"
            )
        );

        return $status;
    }
}
