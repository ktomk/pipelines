<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Definitions;

use Ktomk\Pipelines\TestCase;

/**
 * Class CachesTest
 *
 * @package Ktomk\Pipelines\File\Definitions
 * @covers \Ktomk\Pipelines\File\Definitions\Caches
 */
class CachesTest extends TestCase
{
    public function testCreation()
    {
        $caches = new Caches(array());
        self::assertInstanceOf('Ktomk\Pipelines\File\Definitions\Caches', $caches);
    }

    public function testParseErrorOnInvalidCacheName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('file parse error: cache definition invalid cache name: 0');
        new Caches(array('foo', 'name' => 'path'));
    }

    public function testParseErrorOnInvalidCachePath()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage(
            "file parse error: cache 'foo' should be a string value (it is currently null or empty)"
        );
        new Caches(array('foo' => null, 'name' => 'path'));
    }

    public function testParseErrorOnInvalidCacheDefinition()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage(
            "file parse error: cache 'foo' should be a string (it is currently defined as a boolean)"
        );
        new Caches(array('foo' => false, 'name' => 'path'));
    }

    public function testGetByName()
    {
        $array = array('name' => 'path');
        $caches = new Caches($array);

        self::assertSame('~/.composer/cache', $caches->getByName('composer'), 'default cache');
        self::assertTrue($caches->getByName('docker'), 'internal cache');
        self::assertNull($caches->getByName('wrong-cache-name'), 'undefined, non-default cache');
    }

    public function testGetByNamesForCustomCache()
    {
        $array = array('name' => 'path');
        $caches = new Caches($array);

        self::assertSame(array(), $caches->getByNames(array()));
        self::assertSame($array, $caches->getByNames(array_keys($array)));
    }

    public function testGetByNamesForPredefinedCache()
    {
        $caches = new Caches(array());
        self::assertSame(array('composer' => '~/.composer/cache'), $caches->getByNames(array('composer')));
    }

    public function testGetByNameNormalizesDuplicates()
    {
        $array = array('name' => 'path');
        $caches = new Caches($array);

        self::assertSame($array, $caches->getByNames(array('name', 'name')));
    }
}
