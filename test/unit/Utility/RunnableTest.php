<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\TestCase;

/**
 *
 * @coversNothing we can not cover interface code \Ktomk\Pipelines\Utility\Runnable
 */
class RunnableTest extends TestCase
{
    public function testImplementability()
    {
        $runnable = RunnableTester::create();
        self::assertInstanceOf('Ktomk\Pipelines\Utility\Runnable', $runnable);
    }
}
