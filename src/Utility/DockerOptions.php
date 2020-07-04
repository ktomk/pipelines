<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Docker\ProcessManager;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\Value\Prefix;
use RuntimeException;

/**
 * docker specific options of the pipelines utility
 *
 * --docker-list
 * --docker-kill
 * --docker-clean
 * --docker-zap
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
     * @var ProcessManager
     */
    private $ps;

    /**
     * @param Args $args
     * @param Exec $exec
     * @param string $prefix
     * @param Streams $streams
     *
     * @return DockerOptions
     */
    public static function bind(Args $args, Exec $exec, $prefix, Streams $streams)
    {
        return new self($args, $exec, $prefix, $streams, new ProcessManager($exec));
    }

    /**
     * DockerOptions constructor.
     *
     * @param Args $args
     * @param Exec $exec
     * @param string $prefix
     * @param Streams $streams
     * @param ProcessManager $ps
     */
    public function __construct(Args $args, Exec $exec, $prefix, Streams $streams, ProcessManager $ps)
    {
        $this->args = $args;
        $this->exec = $exec;
        $this->prefix = Prefix::verify($prefix);
        $this->streams = $streams;
        $this->ps = $ps;
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
     *
     * @return void
     */
    public function run()
    {
        $this->parse($this->args, $this->prefix);
    }

    /**
     * @param Args $args
     * @param string $prefix
     *
     * @throws StatusException
     *
     * @return void
     */
    private function parse(Args $args, $prefix)
    {
        $count = 0;
        $status = 0;

        if ($args->hasOption('docker-list')) {
            $count++;
            $status = $this->runPs();
        }

        $hasKill = $args->hasOption('docker-kill');
        $hasClean = $args->hasOption('docker-clean');
        if ($args->hasOption('docker-zap')) {
            $hasKill = $hasClean = true;
        }

        $ids = $this->runGetIds($count, $hasClean || $hasKill, $prefix);

        $status = $this->runKill($status, $count, $hasKill, $prefix);

        $status = $this->runClean($status, $count, $hasClean, $ids);

        if ($count) {
            throw new StatusException('', $status);
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
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
        $prefix = $this->prefix;

        return $this->exec->pass(
            'docker ps -a',
            array(
                '--filter',
                "name=^/${prefix}[-.]",
            )
        );
    }

    /**
     * @param int $count
     * @param bool $flag
     * @param string $prefix
     *
     * @return null|array
     */
    private function runGetIds(&$count, $flag, $prefix)
    {
        if (!$flag) {
            return null;
        }

        $count++;

        return $this->ps->findAllContainerIdsByNamePrefix($prefix);
    }

    /**
     * @param int $status
     * @param int $count
     * @param bool $hasKill
     * @param string $prefix
     *
     * @return int
     */
    private function runKill($status, &$count, $hasKill, $prefix)
    {
        if ($status || !$hasKill) {
            return $status;
        }

        if (!$status && $hasKill) {
            $count++;
            $running = $this->ps->findRunningContainerIdsByNamePrefix($prefix);
            if ($running) {
                $status = $this->ps->kill($running);
            } else {
                $this->info('no containers to kill');
            }
        }

        return $status;
    }

    /**
     * @param int $status
     * @param int $count
     * @param bool $hasClean
     * @param array $ids
     *
     * @return int
     */
    private function runClean($status, &$count, $hasClean, array $ids = null)
    {
        if ($status || !$hasClean) {
            return $status;
        }

        $count++;
        if ($ids) {
            $status = $this->ps->remove($ids);
        } else {
            $this->info('no containers to remove');
        }

        return $status;
    }
}
