<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\TestCase;

/**
 * Class CacheOptionsTest
 *
 * @package Ktomk\Pipelines\Utility
 * @covers \Ktomk\Pipelines\Utility\CacheOptions
 */
class CacheOptionsTest extends TestCase
{
    public function testCreation()
    {
        $cache = CacheOptions::bind(new Args(array()))->run();
        self::assertInstanceOf('Ktomk\Pipelines\Utility\CacheOptions', $cache);
    }

    public function testHasCache()
    {
        $cache = CacheOptions::bind(new Args(array()))->run();
        self::assertTrue($cache->hasCache());

        $cache = CacheOptions::bind(new Args(array('--no-cache')))->run();
        self::assertFalse($cache->hasCache());
    }
}
