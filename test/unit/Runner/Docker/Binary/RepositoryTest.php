<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Binary;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Project;
use Ktomk\Pipelines\Runner\Directories;
use Ktomk\Pipelines\TestCase;

/**
 * Class RepositoryTest
 *
 * @covers \Ktomk\Pipelines\Runner\Docker\Binary\Repository
 */
class RepositoryTest extends TestCase
{
    public function testCreation()
    {
        $repo = Repository::create(new Exec(), new Directories(array('HOME' => '/foo'), new Project('bar')));
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Docker\Binary\Repository', $repo);
    }

    public function testInject()
    {
        $execTester = ExecTester::create($this);
        $execTester->expect('capture', '~~', 'inject docker binary');

        $directories = $this->createMock('Ktomk\Pipelines\Runner\Directories');
        $repo = new Repository(
            $execTester,
            array(),
            $this->createMock('Ktomk\Pipelines\Runner\Docker\Binary\UnPackager')
        );
        $repo->resolve(Repository::PKG_TEST);
        $repo->inject('foobar');
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Binary\Repository::injectPackage
     */
    public function testInjectPackage()
    {
        $repo = $this->createPartialMock(
            'Ktomk\Pipelines\Runner\Docker\Binary\Repository',
            array('inject')
        );
        $repo->injectPackage(Repository::PKG_TEST, 'foobar');
        $this->addToAssertionCount(1);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Binary\Repository::listPackages
     */
    public function testListPackages()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $actual = $repo->listPackages();
        $expected = array(
            Repository::PKG_PREVIOUS,
            Repository::PKG_ATLBBCPP,
            Repository::PKG_INTEGRATE,
            Repository::PKG_TEST,
        );
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testResolve()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        self::assertSame($repo, $repo->resolve(Repository::PKG_INTEGRATE));
        self::assertSame($repo, $repo->resolve(Repository::PKG_TEST));
        $expected = UnpackagerTest::getTestPackage();
        $actual = $repo->asPackageArray();
        self::assertSame($expected, $actual);
    }

    public function testResolveYamlFile()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $testPackagePath = __DIR__ . '/../../../../../lib/package/docker-42.42.1-binsh-test-stub.yml';
        self::assertSame($repo, $repo->resolve($testPackagePath));
        $expected = UnpackagerTest::getTestPackage();
        $actual = $repo->asPackageArray();
        self::assertSame($expected, $actual);
    }

    public function testResolveBinaryPath()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $testBinary = __DIR__ . '/../../../../data/package/docker-test-stub';
        self::assertSame($repo, $repo->resolve($testBinary));
        $expected = array('prep' => array('bin_local' => $testBinary));
        $actual = $repo->asPackageArray();
        self::assertSame($expected, $actual);
    }

    public function testGetBinaryPath()
    {
        $repo = $this->createPartialMock(
            'Ktomk\Pipelines\Runner\Docker\Binary\Repository',
            array('getPackageLocalBinary')
        );
        $expected = '/foo/bin/docker';
        $repo->method('getPackageLocalBinary')->willReturn($expected);
        $actual = $repo->getBinaryPath();
        self::assertSame($expected, $actual);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Binary\Repository::getPackageLocalBinary
     */
    public function testGetPackageLocalBinary()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $testBinary = __DIR__ . '/../../../../data/package/docker-test-stub';
        self::assertSame($repo, $repo->resolve($testBinary));
        $expected = array('prep' => array('bin_local' => $testBinary));
        $package = $repo->asPackageArray();
        $repo->getPackageLocalBinary($package);
        self::assertSame($expected, $package);
    }

    /**
     * @throws \Exception
     */
    public function testResolveThrows()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('not a readable file: ');
        $repo->resolve('bogus-package-name');
    }

    /**
     * unfortunately some inside code on null for the default
     */
    public function testAsPackageArrayDefault()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $package = $repo->asPackageArray();

        self::assertArrayHasKey('name', $package, 'default package has name');
        self::assertSame('docker-19.03.1-linux-static-x86_64', $package['name']);
    }
}
