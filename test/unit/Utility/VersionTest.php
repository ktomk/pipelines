<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\TestCase;

/**
 * Class VersionTest
 *
 * @covers \Ktomk\Pipelines\Utility\Version
 */
class VersionTest extends TestCase
{
    public function testCreation()
    {
        $version = new Version('');
        self::assertInstanceOf('Ktomk\Pipelines\Utility\Version', $version);
    }

    public function testResolution()
    {
        $version = '@.@.@';
        $actual = Version::resolve($version);
        self::assertIsString($actual);
    }

    public function testResolveSourceVersionBuildVersion()
    {
        $version = new Version('1.2.3');

        self::assertSame('1.2.3', $version->resolveSourceVersion());
    }

    public function testResolveSourceVersionPackageVersion()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Version $version */
        $version = $this->getMockBuilder('Ktomk\Pipelines\Utility\Version')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('1.2.3'))
            ->setMethods(array('getBuildVersion', 'getPackageVersion', 'getGitVersion'))
            ->getMock();
        $version->method('getPackageVersion')->willReturn('3.2.1');

        self::assertSame('3.2.1', $version->resolveSourceVersion());
    }

    public function testResolveSourceVersionGitVersion()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Version $version */
        $version = $this->getMockBuilder('Ktomk\Pipelines\Utility\Version')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('1.2.3'))
            ->setMethods(array('getBuildVersion', 'getPackageVersion', 'getGitVersion'))
            ->getMock();
        $version->method('getGitVersion')->willReturn('3.1.2');

        self::assertSame('3.1.2', $version->resolveSourceVersion());
    }

    public function testResolveSourceVersionFallback()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Version $version */
        $version = $this->getMockBuilder('Ktomk\Pipelines\Utility\Version')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('1.2.3'))
            ->setMethods(array('getBuildVersion', 'getPackageVersion', 'getGitVersion'))
            ->getMock();

        self::assertSame('1.2.3', $version->resolveSourceVersion());
    }

    public function testGetPackageVersion()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Version $version */
        $version = $this->getMockBuilder('Ktomk\Pipelines\Utility\Version')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('1.2.3'))
            ->setMethods(array('getInstalledPackages'))
            ->getMock();
        $version->method('getInstalledPackages')->willReturn(array(
            null,
            (object)array('name' => 'foo/le-bar'),
            (object)array('name' => 'ktomk/pipelines', 'version' => '4.4.4'),
        ));

        self::assertSame('4.4.4-composer', $version->getPackageVersion());
    }

    public function testGetPackageVersionUnknownFormat()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Version $version */
        $version = $this->getMockBuilder('Ktomk\Pipelines\Utility\Version')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('1.2.3'))
            ->setMethods(array('getInstalledPackages'))
            ->getMock();
        $version->method('getInstalledPackages')->willReturn(array(
            null,
            (object)array('name' => 'foo/le-bar'),
            (object)array('name' => 'ktomk/pipelines'),
        ));

        self::assertNull($version->getPackageVersion());
    }

    public function testGetGitVersionInNonGitRepo()
    {
        $version = new Version('@.@.@', '@.@.@', sys_get_temp_dir());
        self::assertNull($version->getGitVersion());
    }

    public function testGetGitVersionInInvalidDirectory()
    {
        $version = new Version('@.@.@', '@.@.@', '/dev/null');
        self::assertNull($version->getGitVersion());
    }

    public function testGetBuildVersion()
    {
        $version = new Version('0.1.2', '@.@.@');
        self::assertSame('0.1.2', $version->getBuildVersion());
    }

    public function testGitComposerVersionInjected()
    {
        self::assertSame(array('1.0.0', null), Version::gitComposerVersion('/dev/null', '1.0.0'));
        self::assertSame(
            array(null, 'version format mismatch: 1.0.x'),
            Version::gitComposerVersion('/dev/null', '1.0.x')
        );
    }
}
