<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

use Ktomk\Pipelines\TestCase;

/**
 * Class ArgsBuilderTest
 *
 * @package Ktomk\Pipelines\Runner\Docker
 * @covers \Ktomk\Pipelines\Runner\Docker\ArgsBuilder
 */
class ArgsBuilderTest extends TestCase
{
    public function testOptMap()
    {
        $this->assertSame(array(), ArgsBuilder::optMap('-l', array()));

        $this->assertSame(
            array('--test', 'foo', '--test', 'bar=baz'),
            ArgsBuilder::optMap('--test', array('foo' => null, 'bar' => 'baz'))
        );
    }
}
