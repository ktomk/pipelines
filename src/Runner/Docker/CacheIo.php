<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibFsPath;
use Ktomk\Pipelines\Runner\Runner;

/**
 * Class CacheHandler
 *
 * Directory path related cache operations for docker containers when
 * running pipelines.
 *
 * Delegates between local cache storage (directory based, one tar
 * archive per cache) and (running) step-runner docker container.
 *
 * @package Ktomk\Pipelines\Runner\Docker
 */
class CacheIo
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $cachesDirectory;

    /**
     * @var Streams
     */
    private $streams;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var string
     */
    private $clonePath;

    /**
     * @var bool
     */
    private $noCache = false;

    /**
     * @var AbstractionLayer
     */
    private $dal;

    /**
     * @param Runner $runner
     * @param $id
     *
     * @return CacheIo
     */
    public static function createByRunner(Runner $runner, $id)
    {
        return new self(
            self::runnerCachesDirectory($runner),
            $id,
            $runner->getRunOpts()->getOption('step.clone-path'),
            $runner->getStreams(),
            $runner->getExec(),
            $runner->getFlags()->noCache()
        );
    }

    /**
     * @param Runner $runner
     *
     * @return string path of base-directory in which the pipelines project tar files are located
     */
    public static function runnerCachesDirectory(Runner $runner)
    {
        return (string)$runner->getDirectories()->getBaseDirectory(
            'XDG_CACHE_HOME',
            sprintf(
                'caches/%s',
                $runner->getProject()
            )
        );
    }

    /**
     * CacheIo constructor.
     *
     * @param string $caches path to directory where tar files are stored
     * @param string $id container
     * @param string $clonePath
     * @param Streams $streams
     * @param Exec $exec
     * @param bool $noCache
     */
    public function __construct($caches, $id, $clonePath, Streams $streams, Exec $exec, $noCache)
    {
        $this->id = $id;
        $this->setCachesDirectory($caches);
        $this->clonePath = $clonePath;
        $this->streams = $streams;
        $this->exec = $exec;
        $this->dal = new AbstractionLayerImpl($exec);
        $this->noCache = $noCache;
    }

    /**
     * Deploy step caches into container
     *
     * @param Step $step
     */
    public function deployStepCaches(Step $step)
    {
        $cachesDirectory = $this->cachesDirectory;

        // skip deploying caches if there are no caches
        if ($this->skip($this->noCache, $cachesDirectory, $step)) {
            return;
        }

        $id = $this->id;
        $streams = $this->streams;

        $streams->out("\x1D+++ populating caches...\n");

        foreach ($step->getCaches() as $name => $path) {
            $tarFile = sprintf('%s/%s.tar', $cachesDirectory, $name);
            $tarExists = LibFs::isReadableFile($tarFile);
            $streams->out(sprintf(" - %s %s (%s)\n", $name, $path, $tarExists ? 'hit' : 'miss'));

            if (!$tarExists) {
                continue;
            }

            $containerPath = $this->mapCachePath($path);

            $this->dal->execute($id, array('mkdir', '-p', $containerPath));
            $this->dal->importTar($tarFile, $id, $containerPath);
        }
    }

    /**
     * Capture cache from container
     *
     * @param Step $step
     * @param bool $capture
     */
    public function captureStepCaches(Step $step, $capture)
    {
        // skip capturing if disabled
        if (!$capture) {
            return;
        }

        $cachesDirectory = $this->cachesDirectory;

        // skip capturing caches if there are no caches
        if ($this->skip($this->noCache, $cachesDirectory, $step)) {
            return;
        }

        $id = $this->id;
        $streams = $this->streams;

        $streams->out("\x1D+++ updating caches from container...\n");

        $cachesDirectory = LibFs::mkDir($cachesDirectory, 0700);

        foreach ($step->getCaches() as $name => $path) {
            $tarFile = sprintf('%s/%s.tar', $cachesDirectory, $name);
            $tarExists = LibFs::isReadableFile($tarFile);
            $streams->out(sprintf(" - %s %s (%s)\n", $name, $path, $tarExists ? 'update' : 'create'));
            touch($tarFile);
            chmod($tarFile, 0600);

            $containerPath = $this->mapCachePath($path);
            $this->dal->exportTar($id, $containerPath . '/.', $tarFile);
        }
    }

    /**
     * Map cache path to container path
     *
     * A cache path in a cache definition can be with information for
     * container context, e.g. $HOME, ~ or just being relative to the
     * clone directory.
     *
     * This requires mapping for the "real" path
     *
     * @param string $path component of cache definition
     *
     * @return string
     */
    public function mapCachePath($path)
    {
        $id = $this->id;

        $containerPath = $path;

        // let the container map the path expression
        $out = $this->dal->execute($id, array('/bin/sh', '-c', sprintf('echo %s', $containerPath)));
        if (null !== $out && (0 < strlen($out)) && ('/' === $out[0])) {
            $containerPath = trim($out);
        }

        // fallback to '~' -> /root assumption (can fail easy as root might not be the user)
        if ('~' === $containerPath[0]) {
            $containerPath = '/root/' . ltrim(substr($containerPath, 1), '/');
        }

        // handle relative paths
        if (!LibFsPath::isAbsolute($containerPath)) {
            $containerPath = LibFsPath::normalizeSegments(
                $this->clonePath . '/' . $containerPath
            );
        }

        return $containerPath;
    }

    /**
     * @param bool $noCache
     * @param string $cachesDirectory
     * @param Step $step
     *
     * @return bool|void
     */
    private function skip($noCache, $cachesDirectory, Step $step)
    {
        if ($noCache) {
            return true;
        }

        // caches directory is empty?
        if (empty($cachesDirectory)) {
            return true;
        }

        // step has caches?
        $caches = $step->getCaches()->getIterator();
        if (!count($caches)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $path
     */
    private function setCachesDirectory($path)
    {
        if (!empty($path) && !LibFsPath::isAbsolute($path)) {
            throw new \InvalidArgumentException(sprintf('Caches directory: Not an absolute path: %s', $path));
        }

        $this->cachesDirectory = (string)$path;
    }
}
