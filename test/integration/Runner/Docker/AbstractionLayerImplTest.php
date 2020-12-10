<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\Runner\Docker;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Runner\Docker\AbstractionLayerImpl;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Value\SideEffect\DestructibleString;

/**
 * Class DockerAbstractionLayerTest
 *
 * @package Ktomk\Pipelines\Runner\Docker
 * @coversNothing
 */
class AbstractionLayerImplTest extends TestCase
{
    public function testContainerRemoveIfExist()
    {
        $exec = new Exec();
        $dal = new AbstractionLayerImpl($exec, true);

        $name = 'pipelines-integration-test-containers.dal';
        $actual = $dal->remove($name);
        if (null === $actual) {
            self::assertNull($actual, 'container removal of non-existent container');
        } else {
            self::assertSame($name, $actual, 'container removal');
        }

        return $name;
    }

    /**
     * @depends testContainerRemoveIfExist
     *
     * @param string $name of test container
     *
     * @return string
     */
    public function testLifecycleStartFresh($name)
    {
        $dal = new AbstractionLayerImpl(new Exec(), true);

        $id = $dal->start('ktomk/pipelines:busybox', array('--name', $name));
        self::assertIsString($id);

        $result = $dal->execute($id, array('echo', 'test'));
        self::assertSame('test', $result, 'execute');

        return $id;
    }

    /**
     * @depends testLifecycleStartFresh
     *
     * @param string $id
     */
    public function testLifecycleKillAfterStart($id)
    {
        $dal = new AbstractionLayerImpl(new Exec(), true);

        $actual = $dal->kill($id);
        self::assertIsString($actual);

        $actual = $dal->remove($id, false);
        self::assertIsString($actual, 'remove after kill');
        self::assertSame($id, $actual, 'kill returns the container id if called with id');
    }

    public function testExecuteOnNonExistentContainer()
    {
        $exec = new Exec();
        $dal = new AbstractionLayerImpl($exec);

        $result = $dal->execute('foo-no-there-is', array('echo'));
        self::assertNull($result);
    }

    public function testExportImportTar()
    {
        $tmpDir = DestructibleString::rmDir(LibTmp::tmpDir('pipelines-integration.tar.'));

        $dal = new AbstractionLayerImpl(new Exec());

        $name = 'pipelines-integration-test-containers.dal-tar';
        $dal->remove($name); // allow to re-test if failed and container not yet removed
        $id = $dal->start('ktomk/pipelines:busybox', array('--name', $name));
        self::assertIsString($id);
        $idRemove = new DestructibleString($id, array($dal, 'remove'));

        $actual = $dal->execute($id, array('/bin/sh', '-c', '>/root/file echo "test"'));
        self::assertSame('', $actual, 'create test file');

        $actual = $dal->execute($id, array('ls', '-c1', '/root'));
        self::assertSame('file', $actual, 'test file has been created');

        $tar = $tmpDir . '/root.tar';
        $actual = $dal->exportTar($id, '/root/.', $tar);
        self::assertSame($tar, $actual, 'export tar');

        $actual = $dal->execute($id, array('/bin/sh', '-c', 'rm /root/file'));
        self::assertSame('', $actual, 'remove test file');

        $actual = $dal->execute($id, array('ls', '-c1', '/root'));
        self::assertSame('', $actual, 'test file is removed');

        $actual = $dal->importTar($tar, $id, '/root');
        self::assertTrue($actual, 'import tar');

        $actual = $dal->execute($id, array('ls', '-c1', '/root'));
        self::assertSame('file', $actual, 'test file is imported');

        unset($idRemove);
    }
}
