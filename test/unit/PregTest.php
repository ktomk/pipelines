<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * @covers \Ktomk\Pipelines\Preg
 */
class PregTest extends TestCase
{
    public function testMatch()
    {
        self::assertSame(1, Preg::match('(^(?!-)[A-Za-z0-9._-]+$)', 'foo'));
        self::assertSame(0, Preg::match('(^(?!-)[A-Za-z0-9._-]+$)', '-foo'));
    }

    public function testMatchThrowsOnInvalidPattern()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessageMatches('(preg_match error \(\d+\): "")');
        Preg::match('', '');
    }
}
