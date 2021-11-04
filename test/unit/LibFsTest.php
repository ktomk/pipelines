<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Value\SideEffect\DestructibleString;

/**
 * @covers \Ktomk\Pipelines\LibFs
 */
class LibFsTest extends TestCase
{
    /**
     * @return array
     */
    public function providePortableFilenames()
    {
        return array(
            array('', false),
            array('-', false),
            array('a', true),
            array('a-', true),
            array('-a', false),
            array('rm -rf', false),
            array('rm-rf', true),
            array('-rf', false),
            array('0', true),
            array('00000', true),
            array('_-....', true),
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
        self::assertSame($expected, LibFs::isPortableFilename($filename));
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
        self::assertDirectoryExists($testDir);
        self::assertSame($testDir, $result);
        $result = LibFs::mkDir($testDir);
        self::assertDirectoryExists($testDir);
        self::assertSame($testDir, $result);

        # clean up
        $result = rmdir($testDir);
        self::assertTrue($result);
        self::assertDirectoryNotExist($testDir);

        $result = rmdir($baseDir);
        self::assertTrue($result);
        self::assertDirectoryNotExist($baseDir);
    }

    public function testMkDirSymlink()
    {
        $baseDir = sys_get_temp_dir() . '/pipelines-fs-tests';
        $target = LibFs::mkDir($baseDir . '/target/dir');
        $link = $baseDir . '/link/dir';
        LibFs::symlinkWithParents($target, $link);
        self::assertDirectoryExists($baseDir . '/link');
        self::assertTrue(is_link($link));
        LibFs::rmDir($baseDir);
    }

    public function testRename()
    {
        $baseDir = sys_get_temp_dir() . '/pipelines-fs-tests';
        LibFs::mkDir($baseDir);

        $file = $baseDir . '/test';
        file_put_contents($file, 'DATA');
        self::assertFileExists($file);

        LibFs::rename($file, $file . '.new');
        self::assertFileNotExist($file);
        self::assertFileExists($file . '.new');
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
        self::assertFileExists($file);
        $result = LibFs::rm($file);
        self::assertFileNotExist($file);
        self::assertSame($file, $result);
        $result = LibFs::rm($file);
        self::assertFileNotExist($file);
        self::assertSame($file, $result);
    }

    /**
     * @covers \Ktomk\Pipelines\LibFs::rmDir()
     */
    public function testRmDir()
    {
        $subDir = sys_get_temp_dir() . '/pipelines-fs-tests/subdir';
        LibFs::mkDir($subDir);
        file_put_contents($subDir . '/test', 'DATA');
        self::assertFileExists($subDir . '/test');

        $dir = LibFsPath::normalizeSegments($subDir . '/..');
        self::assertDirectoryExists($dir);
        LibFs::rmDir($dir);
        self::assertDirectoryNotExist($dir);
    }

    /**
     * @covers \Ktomk\Pipelines\LibFs::rmDir
     */
    public function testRmDirOnNonExistingDirectory()
    {
        $dir = LibTmp::tmpDir('pipelines-fs-rmdir-test.') . '/not-existing';
        self::assertDirectoryNotExist($dir);
        LibFs::rmDir($dir);
        $this->addToAssertionCount(1);

        // also test on a previously existing directory
        $dirname = dirname($dir);
        self::assertDirectoryExists($dirname);
        LibFs::rmDir($dirname);
        self::assertDirectoryNotExist($dirname);
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
        self::assertDirectoryExists($dir);
        $subDir = $dir . '/test';
        file_put_contents($subDir, 'DATA');
        self::assertDirectoryNotExist($subDir);
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
        self::assertFileNotExist($link);

        LibFs::mkDir($testDir);
        LibFs::symlink($testDir, $link);
        self::assertFileExists($link);
        LibFs::symlink($testDir, $link);
        self::assertFileExists($link);

        LibFs::unlink($link);
        self::assertFileNotExist($link);

        # clean up
        rmdir($testDir);
        rmdir($baseDir);
        self::assertDirectoryNotExist($testDir);
        self::assertDirectoryNotExist($baseDir);
    }

    public function testFileLookUpSelf()
    {
        $expected = __FILE__;
        $actual = LibFs::fileLookUp(basename($expected), __DIR__);
        self::assertSame($expected, $actual);
    }

    public function testFileLookUpCopying()
    {
        $expected = dirname(dirname(__DIR__)) . '/COPYING';
        $actual = LibFs::fileLookUp(basename($expected), __DIR__);
        self::assertSame($expected, $actual);
    }

    public function testFileLookUpCopyingWorkingDirectory()
    {
        $expected = './COPYING';
        $actual = LibFs::fileLookUp(basename($expected));
        self::assertSame($expected, $actual);
    }

    public function testFileLookUpNonExistingFile()
    {
        $actual = LibFs::fileLookUp('chinese-black-beans-sauce-vs-vietnamese-spring-rolls', __DIR__);
        self::assertNull($actual);
    }

    public function testCanFopen()
    {
        self::assertTrue(LibFs::canFopen(__FILE__, 'rb'));
    }

    public function testCanFopenFail()
    {
        self::assertFalse(LibFs::canFopen(__FILE__ . 'xxxx'));
    }

    public function testIsReadableFile()
    {
        self::assertTrue(LibFs::isReadableFile(__FILE__));
    }

    public function testIsReadableFileOnDirectory()
    {
        self::assertFalse(LibFs::isReadableFile(__DIR__));
    }
}
