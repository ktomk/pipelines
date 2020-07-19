<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\TestCase;

/**
 * Class StepCachesTest
 *
 * @package Ktomk\Pipelines\File\Pipeline
 * @covers \Ktomk\Pipelines\File\Pipeline\StepCaches
 */
class StepCachesTest extends TestCase
{
    public function testCreation()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $caches = new StepCaches($step, array());
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepCaches', $caches);
    }

    public function testCreationParseException()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'caches\' requires a list of caches');

        $yaml = (object)array();
        $caches = new StepCaches($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepCaches', $caches);
    }

    public function testCreationParseExceptionForCacheName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('file parse error: \'caches\' cache name string expected');

        $yaml = array('composer', null);
        $caches = new StepCaches($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepCaches', $caches);
    }

    public function testGetDefinition()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $caches = new StepCaches($step, array());
        self::assertSame(array(), $caches->getDefinitions(), 'no caches if there is no file');

        $step->method('getFile')->willReturn(
            $file = $this->createMock('Ktomk\Pipelines\File\File')
        );
        $file->method('getDefinitions')->willReturn(
            $definitions = $this->createMock('Ktomk\Pipelines\File\Definitions')
        );
        $definitions->method('getCaches')->willReturn(
            $this->createPartialMock('Ktomk\Pipelines\File\Definitions\Caches', array())
        );
        self::assertSame(array(), $caches->getDefinitions(), 'no caches as there are no caches defined');
    }

    public function testGetIterator()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $caches = new StepCaches($step, array());
        self::assertSame(array(), iterator_to_array($caches));
    }
}
