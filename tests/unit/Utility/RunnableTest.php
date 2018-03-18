<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use PHPUnit\Framework\TestCase;

/**
 *
 * @coversNothing we can not cover interface code \Ktomk\Pipelines\Utility\Runnable
 */
class RunnableTest extends TestCase
{
    public function testImplementability()
    {
        $runnable = RunnableTester::create();
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\Runnable', $runnable);
    }
}
