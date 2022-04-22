<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Yaml\Yaml;

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

    public function testCreationParseExceptionForCacheNameBeingNotStringButNull()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('file parse error: \'caches\' cache name string expected');

        $yaml = array(null);
        $caches = new StepCaches($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepCaches', $caches);
    }

    public function testCreationParseExceptionForCacheNameBeingEmptyString()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('file parse error: \'caches\' cache name must not be empty');

        $yaml = array('');
        $caches = new StepCaches($this->createMock('Ktomk\Pipelines\File\Pipeline\Step'), $yaml);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\StepCaches', $caches);
    }

    public function testCreationParseExceptionForUndefinedCustomOrDefaultCache()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage(
            "file parse error: cache 'borked' must reference a custom or default cache definition"
        );

        $file = new File(Yaml::file(__DIR__ . '/../../../data/yml/invalid/caches.yml'));
        $file->getDefault()->getSteps()->offsetGet(0)->getCaches();
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

    public function testParseDefaultCache()
    {
        $file = new File(Yaml::file(__DIR__ . '/../../../data/yml/cache.yml'));
        $stepCaches = $file->getDefault()->getSteps()->offsetGet(0)->getCaches();
        self::assertSame(array('composer', 'docker'), $stepCaches->getNames(), 'parsing worked');
    }

    public function testGetIterator()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $caches = new StepCaches($step, array());
        self::assertSame(array(), iterator_to_array($caches));
    }
}
