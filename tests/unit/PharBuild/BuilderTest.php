<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\PharBuild;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\PharBuild\Builder
 */
class BuilderTest extends TestCase
{
    /**
     * @var string test phar archive file
     */
    private $file;

    /**
     * @var string old working directory
     */
    private $oldPw;

    /**
     * @var Builder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();
        $this->file = sys_get_temp_dir() . '/ptst.phar';
        $this->oldPw = getcwd();
    }

    public function tearDown()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
        if ($this->oldPw && $this->oldPw !== getcwd()) {
            chdir($this->oldPw);
        }

        parent::tearDown();
    }

    public function testCreation()
    {
        $builder = Builder::create('fake.phar');
        $this->assertInstanceOf('Ktomk\Pipelines\PharBuild\Builder', $builder);
    }

    /**
     * @expectedException \PharException
     * @expectedExceptionMessageRegExp ~^illegal stub for phar "/[a-zA-Z0-9/.]+\.phar"$~
     */
    public function testIllegalStub()
    {
        if ("0" !== ini_get('phar.readonly')) {
            $this->markTestSkipped('pnar.readonly is active');
        }
        $builder = Builder::create('fake.phar');
        $builder->stubfile(__FILE__);
        $builder->add(basename(__FILE__), null, __DIR__);
        $builder->build();
    }

    /**
     * php 5.3 crashes with this test (exit status 255)
     * @requires PHP 5.4.0
     */
    public function testDropFirstLineCallbackFileReadError()
    {
        $this->builder = $builder = Builder::create('fake.phar');
        $this->assertFNE($builder);
        $this->expectOutputString("error reading file: data:no-comma-in-URL\n");
        $builder->errHandle = fopen('php://output', 'w');
        $callback = $builder->dropFirstLine();
        $this->assertTrue(is_callable($callback));
        // violate rfc2397 by intention to provoke read error
        $actual = @call_user_func($callback, 'data:no-comma-in-URL');
        $this->assertNull($actual);
    }

    public function testReplaceFileCallback()
    {
        $this->builder = $builder = Builder::create('fake.phar');
        $this->assertFNE($builder);
        $callback = $builder->replace('abc', '123');
        $this->assertTrue(is_callable($callback));
        $result = call_user_func($callback, 'data:,abc');
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertSame('str', $result[0]);
        $this->assertInternalType('string', $result[1]);
        $this->assertSame('123', $result[1]);
    }

    private function assertFNE($actual)
    {
        $builder = $this->builder;
        $this->assertInstanceOf('Ktomk\Pipelines\PharBuild\Builder', $actual);
        $this->assertSame($builder, $actual, 'the same builder');
        $this->assertCount(0, $actual->errors(), 'zero errors');
    }
}
