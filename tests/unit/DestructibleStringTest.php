<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * Class DestructibleStringTest
 *
 * @package Ktomk\Pipelines
 * @covers \Ktomk\Pipelines\DestructibleString
 */
class DestructibleStringTest extends TestCase
{
    public function testCreation()
    {
        $tempDir = new DestructibleString(
            LibTmp::tmpDir('pipelines-test-destruction.'),
            /* @see LibFs::rmDir() */
            __NAMESPACE__ . '\LibFs::rmDir'
        );

        $this->assertDirectoryExists((string)$tempDir);

        return $tempDir;
    }

    public function testRmDir()
    {
        $dir = DestructibleString::rmDir(
            LibTmp::tmpDir('pipelines-test-destruction.')
        );
        $this->assertDirectoryExists((string)$dir);
        $dir->__destruct();
        $this->assertDirectoryNotExists((string)$dir);
    }

    public function testRm()
    {
        list($handle, $tmpFile) = LibTmp::tmpFile();
        $file = DestructibleString::rm($tmpFile);
        $this->assertFileExists((string)$file);
        $file->__destruct();
        $this->assertFileNotExists((string)$file);
        unset($handle);
    }

    /**
     * @depends testCreation
     *
     * @param DestructibleString $tempDir
     */
    public function testDestruction(DestructibleString $tempDir)
    {
        $path = (string)$tempDir;
        $tempDir->__destruct();
        $this->assertDirectoryNotExists($path);
    }
}
