<?php

/* this file is part of pipelines */

/**
 * @link https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 * @link https://blog.jetbrains.com/phpstorm/2019/02/new-phpstorm-meta-php-features/
 */

namespace PHPSTORM_META {

    // pattern example. `@` is replaced by argument literal value.

    override(\PHPUnit\Framework\TestCase::createConfiguredMock(0),
        map([
            '' => '@',
        ])
    );

    override(\PHPUnit\Framework\TestCase::createMock(0),
        map([
            '' => '@',
        ])
    );

    override(\PHPUnit\Framework\TestCase::createPartialMock(0),
        map([
            '' => '@',
        ])
    );

    override(\PHPUnit\Framework\TestCase::getMockForAbstractClass(0),
        map([
            '' => '@',
        ])
    );

}
