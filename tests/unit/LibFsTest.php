<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * @covers \Ktomk\Pipelines\LibFs
 */
class LibFsTest extends TestCase
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
    public function testIsAbsolutePath($path, $expected)
    {
        $actual = LibFs::isAbsolutePath($path);
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
        $actual = LibFs::isBasename($path);
        $this->assertSame($expected, $actual, 'path is (not) basename');
    }

    /**
     * @return array
     */
    public function providePortableFilenames()
    {
        return array(
            array('', false),
            arraY('-', false),
            arraY('a', true),
            arraY('a-', true),
            arraY('rm -rf', false),
            arraY('rm-rf', true),
            arraY('-rf', false),
            arraY('0', true),
            arraY('00000', true),
            arraY('_-....', true),
        );
    }

    /**
     * test if a filename is portable
     *
     * @dataProvider providePortableFilenames()
     * @covers \Ktomk\Pipelines\LibFs::isPortableFilename()
     *
     * @param string $filename
     * @param bool $expected
     */
    public function testIsPortableFilename($filename, $expected)
    {
        $this->assertSame($expected, LibFs::isPortableFilename($filename));
    }

    public function testMkdir()
    {
        $baseDir = sys_get_temp_dir() . '/pipelines-fs-tests';
        $testDir = $baseDir . '/test';
        if (is_dir($testDir)) {
            rmdir($testDir);
        }

        if (is_dir($baseDir)) {
            rmdir($baseDir);
        }

        $result = LibFs::mkDir($testDir);
        $this->assertDirectoryExists($testDir);
        $this->assertSame($testDir, $result);
        $result = LibFs::mkDir($testDir);
        $this->assertDirectoryExists($testDir);
        $this->assertSame($testDir, $result);

        # clean up
        $result = rmdir($testDir);
        $this->assertTrue($result);
        $this->assertDirectoryNotExists($testDir);

        $result = rmdir($baseDir);
        $this->assertTrue($result);
        $this->assertDirectoryNotExists($baseDir);
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
    public function testNormalizePath($path, $expected)
    {
        $this->assertSame($expected, LibFs::normalizePath($path));
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
                '/home/dulcinea/workspace/pipelines/build/store/home'
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
    public function testNormalizePathSegments($path, $expected)
    {
        $this->assertSame($expected, LibFs::normalizePathSegments($path));
    }

    public function testRename()
    {
        $baseDir = sys_get_temp_dir() . '/pipelines-fs-tests';
        LibFs::mkDir($baseDir);

        $file = $baseDir . '/test';
        file_put_contents($file, 'DATA');
        $this->assertFileExists($file);

        LibFs::rename($file, $file . '.new');
        $this->assertFileNotExists($file);
        $this->assertFileExists($file . '.new');
        LibFs::rm($file . '.new');

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage($file);
        LibFs::rename($file, $file . '.new');
    }

    public function testRm()
    {
        $baseDir = sys_get_temp_dir() . '/pipelines-fs-tests';
        LibFs::mkDir($baseDir);

        $file = $baseDir . '/test';
        file_put_contents($file, 'DATA');
        $this->assertFileExists($file);
        $result = LibFs::rm($file);
        $this->assertFileNotExists($file);
        $this->assertSame($file, $result);
        $result = LibFs::rm($file);
        $this->assertFileNotExists($file);
        $this->assertSame($file, $result);
    }

    /**
     * @covers \Ktomk\Pipelines\LibFs::rmDir()
     */
    public function testRmDir()
    {
        $subDir = sys_get_temp_dir() . '/pipelines-fs-tests/subdir';
        LibFs::mkDir($subDir);
        file_put_contents($subDir . '/test', 'DATA');
        $this->assertFileExists($subDir . '/test');

        $dir = LibFs::normalizePathSegments($subDir . '/..');
        $this->assertDirectoryExists($dir);
        LibFs::rmDir($dir);
        $this->assertDirectoryNotExists($dir);
    }

    /**
     * @covers \Ktomk\Pipelines\LibFs::rmDir
     */
    public function testRmDirOnNonExistingDirectory()
    {
        $dir = LibTmp::tmpDir('pipelines-fs-rmdir-test.') . '/not-existing';
        $this->assertDirectoryNotExists($dir);
        LibFs::rmDir($dir);
        $this->addToAssertionCount(1);

        // also test on a previously existing directory
        $dirname = dirname($dir);
        $this->assertDirectoryExists($dirname);
        LibFs::rmDir($dirname);
        $this->assertDirectoryNotExists($dirname);
        LibFs::rmDir($dirname);
        $this->addToAssertionCount(1);
    }

    /**
     * @covers \Ktomk\Pipelines\LibFs::rmDir
     */
    public function testRmDirThrowsExceptionOnNonExistingDirectory()
    {
        $dir = LibTmp::tmpDir('pipelines-fs-rmdir-test.');
        $this->cleaners[] = DestructibleString::rmDir($dir);
        $this->assertDirectoryExists($dir);
        $subDir = $dir . '/test';
        file_put_contents($subDir, 'DATA');
        $this->assertDirectoryNotExists($subDir);
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Failed to open directory');
        LibFs::rmDir($subDir);
    }

    public function testSymlinkAndUnlink()
    {
        $baseDir = sys_get_temp_dir() . '/pipelines-fs-tests';
        $testDir = $baseDir . '/test';
        $link = $baseDir . '/link';
        LibFs::unlink($link);
        $this->assertFileNotExists($link);

        LibFs::mkDir($testDir);
        LibFs::symlink($testDir, $link);
        $this->assertFileExists($link);
        LibFs::symlink($testDir, $link);
        $this->assertFileExists($link);

        LibFs::unlink($link);
        $this->assertFileNotExists($link);

        # clean up
        rmdir($testDir);
        rmdir($baseDir);
        $this->assertDirectoryNotExists($testDir);
        $this->assertDirectoryNotExists($baseDir);
    }

    public function testFileLookUpSelf()
    {
        $expected = __FILE__;
        $actual = LibFs::fileLookUp(basename($expected), __DIR__);
        $this->assertSame($expected, $actual);
    }

    public function testFileLookUpCopying()
    {
        $expected = dirname(dirname(__DIR__)) . '/COPYING';
        $actual = LibFs::fileLookUp(basename($expected), __DIR__);
        $this->assertSame($expected, $actual);
    }

    public function testFileLookUpCopyingWorkingDirectory()
    {
        $expected = './COPYING';
        $actual = LibFs::fileLookUp(basename($expected));
        $this->assertSame($expected, $actual);
    }

    public function testFileLookUpNonExistingFile()
    {
        $actual = LibFs::fileLookUp('chinese-black-beans-sauce-vs-vietnamese-spring-rolls', __DIR__);
        $this->assertNull($actual);
    }

    public function testIsStreamUri()
    {
        $this->assertTrue(LibFs::isStreamUri('data://text/plain,'));
    }

    public function testIsStreamUriOnPath()
    {
        $this->assertFalse(LibFs::isStreamUri(__FILE__));
    }

    public function testCanFopen()
    {
        $this->assertTrue(LibFs::canFopen(__FILE__, 'rb'));
    }

    public function testCanFopenFail()
    {
        $this->assertFalse(LibFs::canFopen(__FILE__ . 'xxxx'));
    }

    public function testIsReadableFile()
    {
        $this->assertTrue(LibFs::isReadableFile(__FILE__));
    }

    public function testIsReadableFileOnDirectory()
    {
        $this->assertFalse(LibFs::isReadableFile(__DIR__));
    }

    public function testIsReadableStream()
    {
        $this->assertTrue(LibFs::isReadableStream(__FILE__), 'standard file');
        $this->assertTrue(LibFs::isReadableStream('php://stdin'), 'php stream: php://stdin');

        $this->assertFalse(LibFs::isReadableStream(null), 'non-string so that resources seem filtered');
        $this->assertFalse(LibFs::isReadableStream('http:'), 'php stream: http:');
        $this->assertFalse(LibFs::isReadableStream('http://'), 'php stream: http://');
        $this->assertFalse(LibFs::isReadableStream('http://ktomk.github.io/'), 'php stream: http://...');
    }

    public function testMapStream()
    {
        $this->assertSame(__FILE__, LibFs::mapStream(__FILE__));
        $this->assertSame('php://stdin', LibFs::mapStream('-'));
        $this->assertSame('php://stdin', LibFs::mapStream('-', 'php://stdin'));
        $this->assertNotSame('php://stdin', LibFs::mapStream('-', null));
        $this->assertSame('php://stdin', LibFs::mapStream('php://stdin', null));
        $this->assertSame('php://fd/11', LibFs::mapStream('/proc/self/fd/11'));
    }
}
