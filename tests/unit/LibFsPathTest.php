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

    public function provideAbsolutePaths() {
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
        $this->assertSame($expected, $actual, "path '${path}' is (not) absolute");
    }

    public function provideBasenamePaths() {
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
        $this->assertSame($expected, $actual, 'path is (not) basename');
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
        $this->assertSame($expected, LibFsPath::normalize($path));
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
            array('make/it/', 'make/it'),
            array('/foo/bar/../baz', '/foo/baz'),
            array(
                '/home/dulcinea/workspace/pipelines/tests/integration/Runner/../../../build/store/home',
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
        $this->assertSame($expected, LibFsPath::normalizeSegments($path));
    }
}
