<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * Class LibFsPathTest
 *
 * @package Ktomk\Pipelines
 * @covers \Ktomk\Pipelines\LibFsPath
 */
class LibFsPathTest extends TestCase
{
    /**
     * Cleanup after test (regardless if succeeded or failed)
     *
     * @var array
     */
    private $cleaners = array();

    public function provideAbsolutePaths()
    {
        return array(
            array('foo.txt', false),
            array('', false),
            array('/', true),
            array('//', false),
            array('///', true),
            array('/foo.txt', true),
            array('bar/foo.txt', false),
            array('/bar/foo.txt', true),
        );
    }

    /**
     * @dataProvider provideAbsolutePaths
     *
     * @param string $path
     * @param bool $expected
     */
    public function testIsAbsolute($path, $expected)
    {
        $actual = LibFsPath::isAbsolute($path);
        self::assertSame($expected, $actual, "path '${path}' is (not) absolute");
    }

    public function provideBasenamePaths()
    {
        return array(
            array('foo.txt', true),
            array('', false),
            array('/', false),
            array('/foo.txt', false),
            array('bar/foo.txt', false),
        );
    }

    /**
     * @dataProvider provideBasenamePaths
     *
     * @param string $path
     * @param bool $expected
     */
    public function testIsBasename($path, $expected)
    {
        $actual = LibFsPath::isBasename($path);
        self::assertSame($expected, $actual, 'path is (not) basename');
    }

    public function providePaths()
    {
        return array(
            array('/foo/../bar', '/bar'), # counter-check for normalizePathSegments
            array('file://foo/../bar', 'file://bar'),
            array('file:///foo/../bar', 'file:///bar'),
            array('phar://foo/../bar', 'phar://bar'),
            array('phar:///foo/../bar', 'phar:///bar'),
        );
    }

    /**
     * @dataProvider providePaths
     *
     * @param string $path
     * @param string $expected
     */
    public function testNormalize($path, $expected)
    {
        self::assertSame($expected, LibFsPath::normalize($path));
    }

    /**
     * @return array
     */
    public function providePathSegments()
    {
        return array(
            array('', ''),
            array('/', '/'),
            array('/.', '/'),
            array('.', ''),
            array('./', ''),
            array('..', '..'),
            array('../', '..'),
            array('/./..', '/..'),
            array('/../..', '/../..'),
            array('make/it/', 'make/it'),
            array('/foo/bar/../baz', '/foo/baz'),
            array(
                '/home/dulcinea/workspace/pipelines/test/integration/Runner/../../../build/store/home',
                '/home/dulcinea/workspace/pipelines/build/store/home',
            ),
            array('////prefix////./ftw/////./////./', '////prefix/ftw'),
            array('./././../././../make/it/../../fake/././it', '../../fake/it'),
        );
    }

    /**
     * @dataProvider providePathSegments
     *
     * @param string $path
     * @param string $expected
     */
    public function testNormalizeSegments($path, $expected)
    {
        self::assertSame($expected, LibFsPath::normalizeSegments($path));
    }

    public function testContainsRelativeSegment()
    {
        self::assertTrue(LibFsPath::containsRelativeSegment('/foo/../bar/baz'));
        self::assertTrue(LibFsPath::containsRelativeSegment('./foo'));
        self::assertFalse(LibFsPath::containsRelativeSegment('')); # flaw
        self::assertFalse(LibFsPath::containsRelativeSegment('/foo'));
    }

    /**
     *
     */
    public function testIsPortable()
    {
        self::assertTrue(LibFsPath::isPortable('aaa'));
        self::assertTrue(LibFsPath::isPortable('/aaa'));
        self::assertTrue(LibFsPath::isPortable('../aaa'));
    }

    public function provideGateAblePortableExpectations()
    {
        return array(
            array(null, 'a'),
            array('/a', '/a'),
            array('/a/a', '/a/a'),
            array(null, '/-a/a'),
            array(null, '/../aa/..'),
        );
    }

    /**
     * @dataProvider provideGateAblePortableExpectations
     *
     * @param mixed $expected
     * @param mixed $path
     */
    public function testGatePortableAbsolute($expected, $path)
    {
        if (null === $expected) {
            $this->expectException('InvalidArgumentException');
        }
        self::assertSame($expected, LibFsPath::gateAbsolutePortable($path));
    }
}
