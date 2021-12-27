<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Provision;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Value\SideEffect\DestructibleString;

/**
 * Copy files and directories (into a running container)
 *
 * This basically works with tar and docker cp as we found it working
 * from very early iterations, therefore the "Tar" class name prefix.
 *
 * Straight extraction from StepRunner for static methods
 */
class TarCopier
{
    /**
     * Make an empty directory with user/group from an existing directory (on
     * the system pipelines is running, the source directory).
     *
     * This ensures the target directory pathname exists within the container
     * on return status 0.
     *
     * If the target pathname is '/', the operation is always successful even
     * no operation is done. reason is that '/' is expected to be already setup.
     *
     * If the target path is empty or relative, the behaviour is undefined.
     *
     * @param Exec $exec
     * @param string $id container id
     * @param string $source directory to obtain file-properties from (user, group)
     * @param string $target directory to create within container with those properties
     *
     * @return int status
     */
    public static function extMakeEmptyDirectory(Exec $exec, $id, $source, $target)
    {
        if ('/' === $target) {
            return 0;
        }

        if ('' === $source) {
            throw new \InvalidArgumentException('empty source');
        }

        if (!is_dir($source)) {
            throw new \InvalidArgumentException("not a directory: '${source}'");
        }

        $tmpDir = DestructibleString::rmDir(LibTmp::tmpDir('pipelines-cp.'));
        LibFs::symlinkWithParents($source, $tmpDir . $target);

        $cd = Lib::cmd('cd', array($tmpDir . '/.'));
        $tar = Lib::cmd('tar', array('c', '-h', '-f', '-', '--no-recursion', '.' . $target));
        $dockerCp = Lib::cmd('docker ', array('cp', '-', $id . ':/.'));

        return $exec->pass("${cd} && ${tar} | ${dockerCp}", array());
    }

    /**
     * Make a (recursive) directory copy from an existing directory (on
     * the system pipelines is running, the source directory) into the
     * containers target directory.
     *
     * The behaviour whether the target directory exists within the container
     * or not depends on the underlying docker cp command. When writing this
     * method assumption is it needs to, {@see TarCopier::extMakeEmptyDirectory()}.
     *
     * If the target path is empty or relative, the behaviour is undefined.
     *
     * @param Exec $exec
     * @param string $id container id
     * @param string $source directory
     * @param string $target directory to create within container
     *
     * @return int status
     */
    public static function extCopyDirectory(Exec $exec, $id, $source, $target)
    {
        if ('' === $source) {
            throw new \InvalidArgumentException('empty source');
        }

        $cd = Lib::cmd('cd', array($source . '/.'));
        $tar = Lib::cmd('tar', array('c', '-f', '-', '.'));
        $dockerCp = Lib::cmd('docker ', array('cp', '-', $id . ':' . $target));

        return $exec->pass("${cd} && ${tar} | ${dockerCp}", array());
    }

    /**
     * Make a (recursive) directory copy from an existing directory (on
     * the system pipelines is running, the source directory) into the
     * containers target directory.
     *
     * The target directory is created in the container with the user/group
     * info from source.
     *
     * @param Exec $exec
     * @param string $id container id
     * @param string $source directory
     * @param string $target directory to create within container
     *
     * @return int
     */
    public static function extDeployDirectory(Exec $exec, $id, $source, $target)
    {
        $status = self::extMakeEmptyDirectory($exec, $id, $source, $target);
        if (0 !== $status) {
            return $status;
        }

        return self::extCopyDirectory($exec, $id, $source, $target);
    }
}
