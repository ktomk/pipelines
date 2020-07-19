<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use InvalidArgumentException;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Runner\Reference
 */
class ReferenceTest extends TestCase
{
    public function testCreation()
    {
        $ref = Reference::create(null);
        self::assertNotNull($ref);
    }

    /**
     * @return array
     */
    public function provideRefs()
    {
        return array(
            array(null, false),
            array('', false),
            array('bar:', false),
            array('tag', false),
            array('tag:', false),
            array('tag:1.0.0', true),
            array('branch:feature/drop-support', true),
            array('pr:feature-42', true),
        );
    }

    /**
     * @dataProvider provideRefs
     *
     * @param string $string
     * @param bool $valid
     */
    public function testValidation($string, $valid)
    {
        self::assertSame($valid, Reference::valid($string), sprintf('reference "%s"', $string));
    }

    /**
     * @dataProvider provideRefs
     *
     * @param $string
     * @param $valid
     */
    public function testParsing($string, $valid)
    {
        try {
            $this->addToAssertionCount(1);
            $type = Reference::create($string);
            if (null !== $string && !$valid) {
                self::fail('An expected exception has not been thrown');
            }
            self::assertNotNull($type);
            if (null !== $string) {
                self::assertNotNull($type->getType());
                self::assertNotNull($type->getName());
            }
        } catch (InvalidArgumentException $e) {
            if ($valid) {
                self::fail('Exception');
            }
        }
    }

    public function testNullObject()
    {
        $ref = new Reference(null);
        self::assertNull($ref->getType());
        self::assertNull($ref->getName());
    }

    public function testGetPipelinesType()
    {
        $f = function ($ref) {
            return Reference::create($ref)->getPipelinesType();
        };

        self::assertNull($f(null));
        self::assertSame('bookmarks', $f('bookmark:stable'));
        self::assertSame('branches', $f('branch:master'));
        self::assertSame('tags', $f('tag:1.0.0'));
        self::assertSame('pull-requests', $f('pr:feature'));
    }
}
