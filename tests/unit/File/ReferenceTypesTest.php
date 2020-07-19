<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\TestCase;

/**
 * Class ReferencesTest
 *
 * @package Ktomk\Pipelines\File
 * @covers \Ktomk\Pipelines\File\ReferenceTypes
 */
class ReferenceTypesTest extends TestCase
{
    /**
     * @return array
     */
    public function provideIds()
    {
        return array(
            array('', false),
            array('default', true),
            array('branch/master', false),
            array('branches/master', true),
            array('custom/foo-le-bar-le-baz', true),
            array("custom/foo-\1-bar", false),
            array('tags/v*', true),
        );
    }

    /**
     * @dataProvider provideIds
     *
     * @param string $id
     * @param bool $expected
     */
    public function testIsValidId($id, $expected)
    {
        self::assertSame($expected, ReferenceTypes::isValidId($id));
    }

    /**
     * @return array
     */
    public function providePatternSections()
    {
        return array(
            array(null, false),
            array('', false),
            array('default', false),
            array('branch', false),
            array('branches', true),
            array('tags', true),
            array('bookmarks', true),
            array('pull-requests', true),
            array('custom', false),
        );
    }

    /**
     * @dataProvider providePatternSections
     *
     * @param null|string $section
     * @param bool $expected
     *
     * @return void
     */
    public function testIsPatternSection($section, $expected)
    {
        self::assertSame($expected, ReferenceTypes::isPatternSection($section));
    }

    /**
     * @return void
     */
    public function testGetSections()
    {
        $actual = ReferenceTypes::getSections();
        self::assertIsArray($actual);
        self::assertContainsOnly('string', $actual, true);
    }

    /**
     * @return array
     */
    public function provideSections()
    {
        return array(
            array(null, false),
            array('', false),
            array('default', false),
            array('branch', false),
            array('branches', true),
            array('tags', true),
            array('bookmarks', true),
            array('pull-requests', true),
            array('custom', true),
        );
    }

    /**
     * @dataProvider provideSections
     *
     * @param null|int|string $section
     * @param bool $expected
     */
    public function testIsSection($section, $expected)
    {
        self::assertSame($expected, ReferenceTypes::isSection($section));
    }
}
