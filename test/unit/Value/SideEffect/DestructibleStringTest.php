<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value\SideEffect;

use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\TestCase;

/**
 * Class DestructibleStringTest
 *
 * @package Ktomk\Pipelines
 * @covers \Ktomk\Pipelines\Value\SideEffect\DestructibleString
 */
class DestructibleStringTest extends TestCase
{
    public function testCreation()
    {
        $tempDir = new DestructibleString(
            LibTmp::tmpDir('pipelines-test-destruction.'),
            /* @see LibFs::rmDir() */
            'Ktomk\Pipelines\LibFs::rmDir'
        );

        self::assertDirectoryExists((string)$tempDir);

        return $tempDir;
    }

    public function testRmDir()
    {
        $dir = DestructibleString::rmDir(
            LibTmp::tmpDir('pipelines-test-destruction.')
        );
        self::assertDirectoryExists((string)$dir);
        $dir->__destruct();
        self::assertDirectoryNotExist((string)$dir);
    }

    public function testRm()
    {
        list($handle, $tmpFile) = LibTmp::tmpFile();
        $file = DestructibleString::rm($tmpFile);
        self::assertFileExists((string)$file);
        $file->__destruct();
        self::assertFileNotExist((string)$file);
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
        self::assertDirectoryNotExist($path);
    }
}
