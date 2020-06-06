<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value;

use Ktomk\Pipelines\TestCase;

/**
 * Class PrefixTest
 *
 * @package Ktomk\Pipelines\Value
 * @covers \Ktomk\Pipelines\Value\Prefix
 */
class PrefixTest extends TestCase
{
    public function testVerify()
    {
        $this->assertSame('foo', Prefix::verify('foo'));

        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('invalid prefix: "p"; a prefix is only lower-case letters with a minimum length of three characters');
        Prefix::verify('p');
    }
}
