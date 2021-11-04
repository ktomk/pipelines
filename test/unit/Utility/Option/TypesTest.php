<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility\Option;

use Ktomk\Pipelines\TestCase;

/**
 * Class TypesTest
 *
 * @covers \Ktomk\Pipelines\Utility\Option\Types
 */
class TypesTest extends TestCase
{
    public function testCreation()
    {
        $types = new Types();
        self::assertNotNull($types);
    }

    public function testAllTypes()
    {
        $types = new Types();
        self::assertSame('foo', $types->verifyInput('foo', null));
        self::assertSame('/foo', $types->verifyInput('/foo', 1));
        self::assertNull($types->verifyInput('foo', 1));
        self::assertNull($types->verifyAbspath('foo/'));

        // now let it crack
        $this->expectException('LogicException');
        $this->expectExceptionMessage('not a type: 2');
        $types->verifyInput('foo', 2);
    }
}
