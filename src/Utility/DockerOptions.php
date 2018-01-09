<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;


use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\Lib;

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

    public static function bind(Args $args, Exec $exec, $prefix, Streams $streams)
    {
        return new self($args, $exec, $prefix, $streams);
    }

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

    /**
     * Process docker related options
     *
     * --docker-list  - list containers
     * --docker-kill  - kill (running) containers
     * --docker-clean - remove (stopped) containers
     *
     * @return int|null for no command executed, otherwise int exist status
     */
    public function run()
    {
        $args = $this->args;
        $exec = $this->exec;
        $prefix = $this->prefix;

        $count = 0;
        $status = 0;

        if (!$status && $args->hasOption('docker-list')) {
            $count++;
            $status = $this->runPs();
        }

        $hasKill = $args->hasOption('docker-kill');
        $hasClean = $args->hasOption('docker-clean');

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
                $this->info("no containers to kill");
            }
        }

        if (!$status && $hasClean) {
            $count++;
            if ($ids) {
                $status = $exec->pass('docker', Lib::merge('rm', $ids));
            } else {
                $this->info("no containers to remove");
            }
        }

        return $count ? $status : null;
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
                "name=^/$prefix-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$"
            )
        );

        return $status;
    }

    /**
     * get ids of all (prefixed in their name) containers, including stopped ones
     *
     * @return array|null
     */
    private function getAllContainerIds()
    {
        $prefix = $this->prefix;

        $ids = null;

        $status = $this->exec->capture(
            'docker',
            array(
                'ps', '-qa', '--filter',
                "name=^/$prefix-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$"
            ),
            $result
        );

        $status || $ids = Lib::lines($result);

        return $ids;
    }

    private function getRunningContainerIds()
    {
        $prefix = $this->prefix;

        $ids = null;

        $status = $this->exec->capture(
            'docker',
            array(
                'ps', '-q', '--filter',
                "name=^/$prefix-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$"
            ),
            $result
        );

        $status || $ids = Lib::lines($result);

        return $ids;
    }
}
