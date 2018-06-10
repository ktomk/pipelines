<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
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
        $exec = $this->exec;

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
            $ids = $this->getAllContainerIds();
        }

        if (!$status && $hasKill) {
            $count++;
            $running = $this->getRunningContainerIds();
            if ($running) {
                $status = $exec->pass('docker', Lib::merge('kill', $running));
            } else {
                $this->info('no containers to kill');
            }
        }

        if (!$status && $hasClean) {
            $count++;
            if ($ids) {
                $status = $exec->pass('docker', Lib::merge('rm', $ids));
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

    /**
     * get ids of all (prefixed in their name) containers, including stopped ones
     *
     * @throws RuntimeException
     * @return null|array
     */
    private function getAllContainerIds()
    {
        $prefix = $this->prefix;

        $ids = null;

        $status = $this->exec->capture(
            'docker',
            array(
                'ps', '-qa', '--filter',
                "name=^/${prefix}-"
            ),
            $result
        );

        $status || $ids = Lib::lines($result);

        return $ids;
    }

    /**
     * @throws RuntimeException
     * @return null|array
     */
    private function getRunningContainerIds()
    {
        $prefix = $this->prefix;

        $ids = null;

        $status = $this->exec->capture(
            'docker',
            array(
                'ps', '-q', '--filter',
                "name=^/${prefix}-"
            ),
            $result
        );

        $status || $ids = Lib::lines($result);

        return $ids;
    }
}
