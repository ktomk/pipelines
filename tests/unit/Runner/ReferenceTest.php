<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use PHPUnit\Framework\TestCase;

class ReferenceTest extends TestCase
{
    public function testCreation()
    {
        $ref = Reference::create(null);
        $this->assertNotNull($ref);
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
        );
    }

    /**
     * @dataProvider provideRefs
     * @param string $string
     * @param bool $valid
     */
    public function testValidation($string, $valid)
    {
        $this->assertSame($valid, Reference::valid($string));
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
            if ($string !== null && !$valid) {
                $this->fail('An expected exception has not been thrown');
            }
            $this->assertNotNull($type);
            if ($string !== null) {
                $this->assertNotNull($type->getType());
                $this->assertNotNull($type->getName());
            }
        } catch (\InvalidArgumentException $e) {
            if ($valid) {
                $this->fail('Exception');
            }
        }
    }

    public function testNullObject()
    {
        $ref = new Reference(null);
        $this->assertNull($ref->getType());
        $this->assertNull($ref->getName());
    }

    public function testGetPipelinesType()
    {
        $f = function ($ref) {
            return Reference::create($ref)->getPipelinesType();
        };

        $this->assertNull($f(null));
        $this->assertSame('bookmarks', $f('bookmark:stable'));
        $this->assertSame('branches', $f('branch:master'));
        $this->assertSame('tags', $f('tag:1.0.0'));
    }
}
