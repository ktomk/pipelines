<?php

/*
 * pipelines - run bitbucket pipelines wherever they dock
 *
 * Copyright 2020, 2021 Tom Klingenberg <ktomk@github.com>
 *
 * Licensed under GNU Affero General Public License v3.0 or later
 *
 * xdebug php loader - development module
 *
 * load php xdebug extension and restart pipelines cli utility with it
 * when --xdebug option is anywhere in the args.
 */

if ($result = array_keys($argv, '--xdebug', true)) {
    $utilPathname = $argv[0];
    $name = basename($utilPathname);
    $serverName = "$name-cli";
    unset($argv[0], $argv[$result[0]]);
    $cmd = Ktomk\Pipelines\Lib::cmd(Ktomk\Pipelines\Lib::phpBinary(), array(
        extension_loaded('xdebug') ? null : '-dzend_extension=xdebug.so',
        /* xdebug 2 */
        (false) ? array(
            '-dxdebug.remote_enable=1',
            '-dxdebug.remote_mode=req',
            '-dxdebug.remote_host=127.0.0.1',
            '-dxdebug.remote_port=9000',
            '-dxdebug.remote_connect_back=0',
        /* xdebug 3 */
        ) : array(
            '-dxdebug.mode=debug',
            '-dxdebug.start_with_request=1',
            '-dxdebug.client_host=127.0.0.1',
            '-dxdebug.client_port=9003',
        ),
        '-dzend.assertions=1',
        '-f', $utilPathname, '--',
        $argv
    ));
    fprintf(
        STDERR,
        "debug: xdebug cli session (server-name: '%s')\n",
        $serverName
    );
    passthru(
        'XDEBUG_CONFIG="" PHP_IDE_CONFIG="serverName=' . $serverName . '" ' .
        $cmd,
        $return_var
    );
    exit($return_var);
};
