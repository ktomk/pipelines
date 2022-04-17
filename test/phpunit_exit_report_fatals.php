<?php

/* this file is part of pipelines */

call_user_func(function () {
    $lastReported = null;

    set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) use (&$lastReported) {
        $lastReported = array(
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
        );

        return false;
    });

    register_shutdown_function(function () use (&$lastReported) {
        /* catch fatal error and report it if it was not yet reported */
        $data = error_get_last();
        $types = array(E_ERROR => 'Fatal', E_COMPILE_ERROR => 'Compile');
        if ($lastReported !== $data && is_array($data) && isset($types[$data['type']])) {
            fwrite(STDERR, "*shutdown-with-fatal*\n");
            fprintf(STDERR, "PHP %s error:  %s in %s:%d\n", $types[$data['type']], $data['message'], $data['file'], $data['line']);
        }
    });
});
