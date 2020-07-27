<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

/**
 * Interface AbstractionLayer
 *
 * So far in Pipelines, esp. inside the runner, docker is executed
 * very directly. Based on the cache implementation, moving it into
 * a layer of it's own after reading Uncle Bobs' Clean Architecture
 * esp. the part about embedded, wondering about dynamic languages
 * and the afterword (vulgo: slap it in and try it out anew)
 *
 * @package Ktomk\Pipelines\Runner\Docker
 */
interface AbstractionLayer
{
    /**
     * @param $id
     * @param array $arguments
     *
     * @return null|string
     */
    public function execute($id, array $arguments);

    /**
     * kill container
     *
     * send KILL signal to container
     *
     * @param string $idOrName
     */
    public function kill($idOrName);

    /**
     * remove container
     *
     * @param $idOrName
     * @param bool $force (optional) defaults to true
     *
     * @return null|string id of the container that was removed, null if there was no container to remove
     */
    public function remove($idOrName, $force = true);

    /**
     * start a docker container (run detached)
     *
     * start a linux docker container detached, hanging in background on a fake
     * interactive terminal for /bin/sh
     *
     * @param string $image
     * @param array|string[] $arguments
     * @param array|string[] $runArguments
     *
     * @return null|string container id
     */
    public function start($image, array $arguments, array $runArguments = array());

    /* tar methods */

    /**
     * Import tar-file into container
     *
     * @param string $tar file path
     * @param string $id container
     * @param string $path container
     *
     * @return null|true
     */
    public function importTar($tar, $id, $path);

    /**
     * Export container directory as tar-file
     *
     * @param string $id container
     * @param string $path container
     * @param string $tar path to tar file to create (directory must exist)
     *
     * @return null|string path of the tar file or null on failure
     */
    public function exportTar($id, $path, $tar);

    /* layer error/exception handling */

    /**
     * docker abstraction layer throw behaviour
     *
     * whether or not the dal throws when performing an action.
     *
     * the default (optional) behaviour is undefined
     *
     * @param bool $throws (optional)
     */
    public function throws($throws = null);
}
