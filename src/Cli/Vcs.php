<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

/**
 * VCS adapter - default implementation is Git
 *
 * @codeCoverageIgnore
 */
class Vcs
{
    /**
     * @var Exec
     */
    private $exec;

    public function __construct(Exec $exec)
    {
        $this->exec = $exec;
    }

    /**
     * @throws \RuntimeException
     *
     * @return null|string
     */
    public function getTopLevelDirectory()
    {
        $result = $this->exec->capture('git', array('rev-parse', '--show-toplevel'), $out);
        if (0 !== $result) {
            return null;
        }

        $path = substr($out, 0, -1);

        if (!is_dir($path)) {
            return null;
        }

        return $path;
    }
}
