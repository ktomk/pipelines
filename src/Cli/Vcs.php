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
    private $exec;

    public function __construct()
    {
        $this->exec = new Exec();
    }

    /**
     * @return false|null|string
     */
    public function getToplevelDirectory()
    {
        $result = $this->exec->capture('git', array('rev-parse', '--show-toplevel'));
        if ($result->getStatus() !== 0) {
            return null;
        }

        $path = substr($result->getStandardOutput(), -1);

        return $path;
    }
}
