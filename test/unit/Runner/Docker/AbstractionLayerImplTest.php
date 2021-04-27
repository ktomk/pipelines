<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\TestCase;

/**
 * Class AbstractionLayerImplTest
 *
 * @package Ktomk\Pipelines\Runner\Docker
 * @covers \Ktomk\Pipelines\Runner\Docker\AbstractionLayerImpl
 */
class AbstractionLayerImplTest extends TestCase
{
    public function testCreation()
    {
        $dal = new AbstractionLayerImpl($this->createMock('Ktomk\Pipelines\Cli\Exec'));
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Docker\AbstractionLayerImpl', $dal);
    }

    public function testExecute()
    {
        $exec = new ExecTester($this);
        $dal = new AbstractionLayerImpl($exec);

        $id = 'foo';

        $exec->expect('capture', 'docker exec foo echo test', 'test');
        $result = $dal->execute($id, array('echo', 'test'));

        self::assertSame('test', $result);

        $exec->expect('capture', 'docker exec foo false', 1);
        $dal->throws(false);
        $actual = $dal->execute($id, array('false'));
        self::assertNull($actual, 'execute failure (non-zero status)');

        $exec->expect('capture', 'docker exec foo false', 1);
        $dal->throws();
        $this->expectDalException();
        $dal->execute($id, array('false'));
    }

    public function testKill()
    {
        $exec = new ExecTester($this);
        $dal = new AbstractionLayerImpl($exec);

        $exec->expect('capture', 'docker kill foo', 'foo');
        self::assertSame('foo', $dal->kill('foo'));

        $exec->expect('capture', 'docker kill foo', 1);
        $dal->throws(false);
        self::assertNull($dal->kill('foo'));

        $exec->expect('capture', 'docker kill foo', 1);
        $dal->throws();
        $this->expectDalException();
        $dal->kill('foo');
    }

    public function testRemove()
    {
        $exec = new ExecTester($this);
        $dal = new AbstractionLayerImpl($exec);

        $exec->expect('capture', 'docker rm -f foo');
        $result = $dal->remove('foo');
        self::assertIsString($result, 'remove happy path (default forced)');

        $exec->expect('capture', 'docker rm foo', 'foo');
        $result = $dal->remove('foo', false);
        self::assertSame($result, 'foo', 'remove happy path (not enforced)');

        $exec->expect('capture', 'docker rm foo', 1);
        $result = $dal->remove('foo', false);
        self::assertNull($result, 'remove happy path of non-existent container (not enforced)');

        $exec->expect('capture', 'docker rm foo', function ($command, $arguments, &$out, &$err) {
            $err = "Error: No such container: foo\n";

            return 0;
        });
        $result = $dal->remove('foo', false);
        self::assertNull($result, 'remove happy path of non-existent container (not enforced, docker 20.10.5, build 55c4c88)');

        // remove (while not throwing; tests!) and some general error
        $exec->expect('capture', 'docker rm -f foo', 42);
        $dal->throws(false);
        self::assertNull($dal->remove('foo'));
    }

    public function testStart()
    {
        $exec = new ExecTester($this);
        $dal = new AbstractionLayerImpl($exec);

        $exec->expect('capture', 'docker run --detach --entrypoint /bin/sh -it foo');
        $result = $dal->start('foo', array());
        self::assertIsString($result);

        $exec->expect('capture', 'docker run --detach --entrypoint /bin/sh -it foo', 1);
        $dal->throws(false);
        $result = $dal->start('foo', array());
        self::assertNull($result);
    }

    /* tar methods */

    public function testImportTar()
    {
        $exec = new ExecTester($this);
        $dal = new AbstractionLayerImpl($exec);

        $exec->expect('capture', "</fake.tar docker cp - 'foo:/app/fake'");
        $actual = $dal->importTar('/fake.tar', 'foo', '/app/fake');
        self::assertTrue($actual, 'import tar');

        $exec->expect('capture', "</fake.tar docker cp - 'foo:/app/fake'", 42);
        $dal->throws(false);
        $actual = $dal->importTar('/fake.tar', 'foo', '/app/fake');
        self::assertNull($actual, 'import tar failure');
    }

    public function testExportTar()
    {
        $exec = new ExecTester($this);
        $dal = new AbstractionLayerImpl($exec);

        $exec->expect('capture', ">/fake.tar docker cp 'foo:/app/fake' -");
        $actual = $dal->exportTar('foo', '/app/fake', '/fake.tar');
        self::assertSame('/fake.tar', $actual, 'export tar');

        $exec->expect('capture', ">/fake.tar docker cp 'foo:/app/fake' -", 42);
        $dal->throws(false);
        $actual = $dal->exportTar('foo', '/app/fake', '/fake.tar');
        self::assertNull($actual, 'export tar failure');
    }

    /* internal */

    private function expectDalException()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessageMatches('~^Failed to execute\n\n  ~');
    }
}
