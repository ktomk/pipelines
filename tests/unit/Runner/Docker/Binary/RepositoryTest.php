<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Binary;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\DestructibleString;
use Ktomk\Pipelines\LibFs;
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
        $repo = Repository::create(new Exec(), new Directories(array('HOME' => '/foo'), 'bar'));
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Docker\Binary\Repository', $repo);
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
     * @throws \Exception
     */
    public function testResolve()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $this->assertSame($repo, $repo->resolve(Repository::PKG_INTEGRATE));
        $this->assertSame($repo, $repo->resolve(Repository::PKG_TEST));
        $expected = UnpackagerTest::getTestPackage();
        $actual = $repo->asPackageArray();
        $this->assertSame($expected, $actual);
    }

    public function testResolveYamlFile()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $testPackagePath = __DIR__ . '/../../../../../lib/package/docker-42.42.1-binsh-test-stub.yml';
        $this->assertSame($repo, $repo->resolve($testPackagePath));
        $expected = UnpackagerTest::getTestPackage();
        $actual = $repo->asPackageArray();
        $this->assertSame($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testResolveThrows()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $this->setExpectedException('InvalidArgumentException', 'not a readable file: ');
        $repo->resolve('bogus-package-name');
    }

    /**
     * unfortunately some inside code on null for the default
     */
    public function testAsPackageArrayDefault()
    {
        $repo = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\Binary\Repository', array());
        $package = $repo->asPackageArray();

        $this->assertArrayHasKey('name', $package, 'default package has name');
        $this->assertSame('docker-19.03.1-linux-static-x86_64', $package['name']);
    }
}
