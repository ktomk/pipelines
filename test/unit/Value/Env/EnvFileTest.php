<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value\Env;

use Ktomk\Pipelines\LibFsPath;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Value\Env\EnvFile
 */
class EnvFileTest extends TestCase
{
    public function testCreation()
    {
        $file = new EnvFile(__DIR__ . '/../../../../.env.dist');
        self::assertNotNull($file);
    }

    public function testStringable()
    {
        $path = __DIR__ . '/../../../../.env.dist';
        $file = new EnvFile($path);
        self::assertSame($path, (string)$file);
    }

    public function testLoad()
    {
        $file = new EnvFile(__DIR__ . '/../../../../.env.dist');
        $this->addToAssertionCount(1);
    }

    public function testLoadThrowsWithNonExistingFile()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("File read error: 'xxx'");
        new EnvFile( 'xxx');
    }

    public function testLoadBrokenFileThrows()
    {
        $pathToBrokenEnvFile = LibFsPath::normalizeSegments(__DIR__ . '/../../../data/env/broken.env');

        try {
            new EnvFile($pathToBrokenEnvFile);
            self::fail('an expected exception was not thrown.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringStartsWith(
                sprintf('%s:2 Environment variable error: {"database"', $pathToBrokenEnvFile),
                $exception->getMessage()
            );
            self::assertNotNull($fileError = $exception->getPrevious());
            self::assertSame($pathToBrokenEnvFile, $fileError->getFile());
            self::assertSame(2, $fileError->getLine());
        }
    }

    public function testEnvEnumeration()
    {
        $path = LibFsPath::normalizeSegments(__DIR__ . '/../../../data/env/.env');
        $file = new EnvFile($path);

        self::assertInstanceOf('Countable', $file);
        self::assertInstanceOf('Traversable', $file);

        $expectedCount = 3;
        self::assertCount($expectedCount, $file);
        $count = 0;
        foreach ($file as $var) {
            self::assertInstanceOf(__NAMESPACE__ . '\\EnvVar', $var);
            $count++;
        }
        self::assertSame($expectedCount, $count);
    }

    public function testGetPairs()
    {
        $path = LibFsPath::normalizeSegments(__DIR__ . '/../../../data/env/.env');
        $file = new EnvFile($path);
        $pairs = $file->getPairs();
        self::assertSame(array(
            array('DOCKER_ID_USER', 'l-oracle-de-delphi'),
            array('DOCKER_ID_PASSWORD', 'ThePastIsTheSecretOfTheFuture'),
            array('FOO', 'BAZ'),
        ), $pairs);
    }
}
