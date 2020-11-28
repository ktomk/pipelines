<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Value\SideEffect\DestructibleString;

/**
 * @covers \Ktomk\Pipelines\LibTmp
 */
class LibTmpTest extends TestCase
{
    /**
     * @var array
     */
    private $cleaners = array();

    public function testTmpDir()
    {
        $dir = LibTmp::tmpDir('pipelines-fs-rmdir-test.');
        $this->cleaners[] = DestructibleString::rmDir($dir);

        self::assertDirectoryExists($dir);
    }

    /**
     * @covers \Ktomk\Pipelines\LibTmp::tmpFile
     */
    public function testTmpFile()
    {
        list($handle, $file) = LibTmp::tmpFile();
        self::assertFileExists($file);
        unset($handle);
        self::assertFileNotExist($file);
    }

    /**
     * @covers \Ktomk\Pipelines\LibTmp::tmpFilePut
     */
    public function testTmpFilePut()
    {
        $file = LibTmp::tmpFilePut('FOO');
        self::assertFileExists($file);
        $actual = file_get_contents($file);
        LibFs::rm($file);
        self::assertSame('FOO', $actual);
        self::assertFileNotExist($file);
    }
}
