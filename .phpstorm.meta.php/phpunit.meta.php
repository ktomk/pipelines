<?php

/* this file is part of pipelines */

/**
 * @link https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 * @link https://blog.jetbrains.com/phpstorm/2019/02/new-phpstorm-meta-php-features/
 */

namespace PHPSTORM_META {

    // pattern example. `@` is replaced by argument literal value.

    /** @scrutinizer ignore-call */
    override(\PHPUnit\Framework\TestCase::/** @scrutinizer ignore-call */ createMock(0),
        /** @scrutinizer ignore-call */
        map([
            '' => '@|\PHPUnit\Framework\MockObject\MockObject',
        ])
    );

    /** @scrutinizer ignore-call */
    override(\PHPUnit\Framework\TestCase::/** @scrutinizer ignore-call */ createPartialMock(0),
        /** @scrutinizer ignore-call */
        map([
            '' => '@|\PHPUnit\Framework\MockObject\MockObject',
        ])
    );

}
