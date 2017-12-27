<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Runner\Env;

/**
 * Pipeline runner with docker under the hood
 */
class Runner
{
    const FLAGS = 3;
    const FLAG_REMOVE = 1;
    const FLAG_KILL = 2;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $directory;

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
     * DockerSession constructor.
     *
     * @param string $prefix
     * @param string $directory source repository root
     * @param Exec $exec
     * @param int $flags [optional]
     * @param Env|null $env
     */
    public function __construct($prefix, $directory, Exec $exec, $flags = null, Env $env = null)
    {
        $this->prefix = $prefix;

        $this->directory = $directory;
        $this->exec = $exec;
        $this->flags = $flags === null ? self::FLAGS : $flags;
        $this->env = $env;
    }

    public function run(Pipeline $pipeline)
    {
        $prefix = $this->prefix;
        $dir = $this->directory;
        $exec = $this->exec;
        $env = $this->env ?: $env = Env::create();;

        $steps = $pipeline->getSteps();
        foreach ($steps as $index => $step) {
            $name = $prefix . '-' . Lib::generateUuid();
            $image = $step->getImage();

            # launch container
            printf(
                "\x1D+++ step #%d\n\n    name...........: %s\n    effective-image: %s\n    container......: %s\n\n",
                $index,
                $step->getName() ? '"' . $step->getName() . '"' : '(unnamed)',
                $step->getImage(),
                $name
            );

            $status = $exec->capture('docker', array(
                'run', '-i', '--name', $name,
                $env->getArgs('-e'),
                '--volume', "$dir:/app", '-e', 'BITBUCKET_CLONE_DIR=/app',
                '--workdir', '/app', '--detach', $image
            ), $out, $err);
            if ($status !== 0) {
                printf("fatal: setting up the container failed.\n");
                echo $out, "\n", $err, "\n";
                printf("exit status: %d\n", $status);
                break;
            }

            $script = $step->getScript();
            foreach ($script as $line => $command) {
                printf("\x1D+ %s\n", $command);
                $status = $exec->pass('docker', array(
                    'exec', '-i', $name, '/bin/sh', '-c', $command,
                ));
                printf("\n");
                if ($status !== 0) {
                    break;
                }
            }

            # remove container
            if ($this->flags & self::FLAG_KILL) {
                $exec->capture('docker', array('kill', $name));
            }
            if ($this->flags & self::FLAG_REMOVE) {
                $exec->capture('docker', array('rm', $name));
            }
        }

        if (!isset($status)) {
            return 255;
        }

        return $status;
    }
}
