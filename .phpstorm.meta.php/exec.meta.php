<?php

/* this file is part of pipelines */

/**
 * @link https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 * @link https://blog.jetbrains.com/phpstorm/2019/02/new-phpstorm-meta-php-features/
 */

namespace PHPSTORM_META {

    registerArgumentsSet(
        'exec_methods',
        'capture', 'pass'
    );

    expectedArguments(
        \Ktomk\Pipelines\Cli\ExecTester::expect(),
        0,
        argumentsSet('exec_methods')
    );

}
