<?php

/*
 * pipelines - run bitbucket pipelines wherever they dock
 *
 * Copyright 2020 Tom Klingenberg <ktomk@github.com>
 *
 * Licensed under GNU Affero General Public License v3.0 or later
 *
 * xdebug php loader - development module
 *
 * load php xdebug extension and restart pipelines cli utility with it
 * when --xdebug option is anywhere in the args.
 */

if ($result = array_keys($argv, '--xdebug', true)) {
    $util = $argv[0];
    $name = basename($util);
    unset($argv[0], $argv[$result[0]]);
    $cmd = Ktomk\Pipelines\Lib::cmd(Ktomk\Pipelines\Lib::phpBinary(), array(
        extension_loaded('xdebug') ? null : '-dzend_extension=xdebug.so',
        '-dxdebug.remote_enable=1', '-dxdebug.remote_mode=req', '-dxdebug.remote_port=9000',
        '-dxdebug.remote_host=127.0.0.1', '-dxdebug.remote_connect_back=0',
        '-f', $util, '--',
        $argv
    ));
    fprintf(
        STDERR,
        "debug: xdebug cli session (server-name: '%s')\n",
        $name
    );
    passthru(
        'XDEBUG_CONFIG="" PHP_IDE_CONFIG="serverName=' . $name . '-cli" ' .
        $cmd,
        $return_var
    );
    exit($return_var);
};
