<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\PharBuild;

use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\TestCase;
use ReflectionProperty;

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

    public function doSetUp()
    {
        parent::doSetUp();
        $this->file = sys_get_temp_dir() . '/ptst.phar';
        $this->oldPw = getcwd();
    }

    public function doTearDown()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
        if ($this->oldPw && $this->oldPw !== getcwd()) {
            chdir($this->oldPw);
        }

        parent::doTearDown();
    }

    public function testCreation()
    {
        $builder = Builder::create('fake.phar');
        self::assertInstanceOf('Ktomk\Pipelines\PharBuild\Builder', $builder);
    }

    /**
     */
    public function testIllegalStub()
    {
        $this->expectException('PharException');
        $this->expectExceptionMessageMatches('~^illegal stub for phar "/[a-zA-Z0-9/._]+\\.phar"(|\Q (__HALT_COMPILER\E\Q(); is missing)\E)$~');

        $this->needsPharWriteAccess();

        $builder = Builder::create('fake.phar');
        $builder->stubfile(__FILE__);
        $builder->add(basename(__FILE__), null, __DIR__);
        $builder->build();
    }

    public function testDropFirstLineCallbackFileReadError()
    {
        $this->builder = $builder = Builder::create('fake.phar');
        $this->assertFNE($builder);
        $this->expectOutputString("error reading file: data:no-comma-in-URL\n");
        $builder->errHandle = fopen('php://output', 'wb');
        $callback = $builder->dropFirstLine();
        self::assertIsCallable($callback);
        // violate rfc2397 by intention to provoke read error
        $actual = @call_user_func($callback, 'data:no-comma-in-URL');
        self::assertNull($actual);
    }

    public function testReplaceFileCallback()
    {
        $this->builder = $builder = Builder::create('fake.phar');
        $this->assertFNE($builder);
        $callback = $builder->replace('abc', '123');
        self::assertIsCallable($callback);
        $result = call_user_func($callback, 'data:,abc');
        self::assertArrayHasKey(0, $result);
        self::assertArrayHasKey(1, $result);
        self::assertSame('str', $result[0]);
        self::assertIsString($result[1]);
        self::assertSame('123', $result[1]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testBuildFilesInvalidType()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('unknown type: type');

        $this->needsPharWriteAccess();
        $this->builder = $builder = Builder::create('fake.phar');
        $this->assertFNE($builder);

        $filesProperty = new ReflectionProperty($builder, 'files');
        $filesProperty->setAccessible(true);
        $filesProperty->setValue($builder, array('.null' => array('type', 'context')));

        $builder->build();
    }

    /**
     * test phpExec() will trigger which resolution and PHP_BINARY mapping
     */
    public function testPhpExec()
    {
        $testCase = $this;
        $expected = sprintf('~^%s -f (?:/usr)?/bin/test -- $~', preg_quote(Lib::phpBinary(), '~'));

        $builder = $this->createPartialMock('Ktomk\Pipelines\PharBuild\Builder', array('exec'));
        $builder
            ->method('exec')
            ->willReturnCallback(function ($command, &$return) use ($testCase, $expected, $builder) {
                $testCase::assertMatchesRegularExpression($expected, $command);
                $return = 'OK';

                return $builder;
            });

        self::assertSame($builder, $builder->phpExec('test', $return));
        self::assertSame('OK', $return);
    }

    public function testPhpExecWithInvalidCommand()
    {
        $this->builder = $builder = Builder::create('fake.phar');
        $this->assertFNE($builder);
        $this->expectOutputString("php command error: unable to resolve \"not-existing-fake-utility\", verify the file exists and it is an actual php utility\n");
        $builder->errHandle = fopen('php://output', 'wb');
        $builder->phpExec('not-existing-fake-utility');
    }

    private function assertFNE(Builder $actual)
    {
        $builder = $this->builder;
        self::assertInstanceOf('Ktomk\Pipelines\PharBuild\Builder', $actual);
        self::assertSame($builder, $actual, 'the same builder');
        self::assertCount(0, $actual->errors(), 'zero errors');
    }

    private function needsPharWriteAccess()
    {
        if ('0' !== ini_get('phar.readonly')) {
            self::markTestSkipped('phar.readonly is active');
        }
    }
}
