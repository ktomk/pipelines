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
     * @param string $path
     * @param bool $expected
     */
    public function testIsBasename($path, $expected)
    {
        $actual = LibFs::isBasename($path);
        $this->assertSame($expected, $actual, 'path is (not) basename');
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

        $this->setExpectedException('RuntimeException', $file);
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
        $dir = LibFs::tmpDir('pipelines-fs-rmdir-test.') . '/not-existing';
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
        $dir = LibFs::tmpDir('pipelines-fs-rmdir-test.');
        $this->cleaners[] = DestructibleString::rmDir($dir);
        $this->assertDirectoryExists($dir);
        $subDir = $dir . '/test';
        file_put_contents($subDir, 'DATA');
        $this->assertDirectoryNotExists($subDir);
        $this->setExpectedException('UnexpectedValueException', 'Failed to open directory');
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

    /**
     * @covers \Ktomk\Pipelines\LibFs::tmpFile
     */
    public function testTmpFile()
    {
        list($handle, $file) = LibFs::tmpFile();
        $this->assertFileExists($file);
        unset($handle);
        $this->assertFileNotExists($file);
    }

    /**
     * @covers \Ktomk\Pipelines\LibFs::tmpFilePut
     */
    public function testTmpFilePut()
    {
        $file = LibFs::tmpFilePut('FOO');
        $this->assertFileExists($file);
        $actual = file_get_contents($file);
        LibFs::rm($file);
        $this->assertSame('FOO', $actual);
        $this->assertFileNotExists($file);
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
}
