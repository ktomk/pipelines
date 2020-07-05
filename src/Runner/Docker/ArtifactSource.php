<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Glob;

class ArtifactSource
{
    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var string container id
     */
    private $id;

    /**
     * @var string in-container build directory
     */
    private $dir;

    /**
     * @var null|array all in-container build directory file paths
     */
    private $allFiles;

    /**
     * ArtifactSource constructor.
     *
     * @param Exec $exec
     * @param string $id container id
     * @param string $dir in-container build directory ($BITBUCKET_CLONE_DIR)
     */
    public function __construct(Exec $exec, $id, $dir)
    {
        $this->exec = $exec;
        $this->id = $id;
        $this->dir = $dir;
    }

    /**
     * @throws \RuntimeException
     *
     * @return array|string[]
     */
    public function getAllFiles()
    {
        if (null === $this->allFiles) {
            $this->allFiles = $this->getFindPaths();
        }

        return $this->allFiles;
    }

    /**
     * @param string $pattern
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public function findByPattern($pattern)
    {
        /**
         * @param $subject
         *
         * @return bool
         */
        $matcher = function ($subject) use ($pattern) {
            return Glob::match($pattern, $subject);
        };

        $paths = $this->getAllFiles();

        $found = array_filter($paths, $matcher);

        return array_values($found);
    }

    /**
     * @return string container id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * get an array of paths obtained via docker exec & find
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    private function getFindPaths()
    {
        $buffer = $this->getFindBuffer();

        $lines = explode("\n", trim($buffer, "\n"));
        $pattern = '~^\\./~';
        $existing = preg_grep($pattern, $lines);
        $paths = preg_replace($pattern, '', $existing);

        return array_values($paths);
    }

    /**
     * @throws \RuntimeException
     *
     * @return string
     */
    private function getFindBuffer()
    {
        $command = array(
            'find', '(', '-name', '.git', '-o', '-name', '.idea', ')',
            '-prune', '-o', '(', '-type', 'f', '-o', '-type', 'l', ')',
        );

        $status = $this->exec->capture('docker', array(
            'exec', '-w', $this->dir, $this->id, $command,
        ), $out);

        if (0 === $status) {
            return $out;
        }

        return '';
    }
}
