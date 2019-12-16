<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

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

        $this->assertDirectoryExists($dir);
    }

    /**
     * @covers \Ktomk\Pipelines\LibTmp::tmpFile
     */
    public function testTmpFile()
    {
        list($handle, $file) = LibTmp::tmpFile();
        $this->assertFileExists($file);
        unset($handle);
        $this->assertFileNotExists($file);
    }

    /**
     * @covers \Ktomk\Pipelines\LibTmp::tmpFilePut
     */
    public function testTmpFilePut()
    {
        $file = LibTmp::tmpFilePut('FOO');
        $this->assertFileExists($file);
        $actual = file_get_contents($file);
        LibFs::rm($file);
        $this->assertSame('FOO', $actual);
        $this->assertFileNotExists($file);
    }
}
