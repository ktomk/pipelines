<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Binary;

use Ktomk\Pipelines\LibFsPath;
use Ktomk\Pipelines\TestCase;

/**
 * Class BinaryPackageYmlReaderTest
 *
 * @covers \Ktomk\Pipelines\Runner\Docker\Binary\PackageYamlFileReader
 */
class PackageYamlFileReaderTest extends TestCase
{
    public function testReadingAndResolvingOfTestPackage()
    {
        $testPackage = LibFsPath::normalizeSegments(
            __DIR__ . '/../../../../../lib/package/docker-42.42.1-binsh-test-stub.yml'
        );

        $reader = new PackageYamlFileReader($testPackage);
        $expected = UnpackagerTest::getTestPackage();
        $actual = $reader->asPackageArray();
        self::assertSame($expected, $actual);
    }

    /**
     *
     */
    public function provideTestPackages()
    {
        return array(
            array(__DIR__ . '/fixtures/no-uri.yml'),
            array(__DIR__ . '/fixtures/http-uri.yml'),
            array(__DIR__ . '/fixtures/absolute-path-uri.yml'),
        );
    }

    /**
     * @dataProvider provideTestPackages
     *
     * @param string $file
     */
    public function testReadingTestPackages($file)
    {
        $reader = new PackageYamlFileReader($file);
        $actual = $reader->asPackageArray();
        self::assertIsArray($actual);
    }

    public function testReadingInexistentPackageFile()
    {
        $file = '';
        $reader = new PackageYamlFileReader($file);
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('not a readable file: ');
        $reader->asPackageArray();
    }
}
