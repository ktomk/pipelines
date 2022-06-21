<?php

/* this file is part of pipelines */

/**
 * @link https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 * @link https://blog.jetbrains.com/phpstorm/2019/02/new-phpstorm-meta-php-features/
 */

namespace PHPSTORM_META {

    registerArgumentsSet(
        'option_names',
        'docker.client.path', 'docker.socket.path', 'script.bash-runner', 'script.exit-early', 'script.runner',
        'step.clone-path'
    );

    expectedArguments(
        \Ktomk\Pipelines\Utility\Options::get(),
        0,
        argumentsSet('option_names')
    );

    expectedArguments(
        \Ktomk\Pipelines\Utility\OptionsMock::define(),
        0,
        argumentsSet('option_names')
    );

    expectedArguments(
        \Ktomk\Pipelines\Runner\RunOpts::getOption(),
        0,
        argumentsSet('option_names')
    );

}
