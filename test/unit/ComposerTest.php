<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * @covers \Ktomk\Pipelines\Composer
 */
class ComposerTest extends TestCase
{
    /**
     * in test the command is phpunit
     *
     * @return void
     */
    public function testWhichScript()
    {
        $this->expectOutputRegex('~/phpunit$~');
        Composer::which();
    }

    /**
     * @return void
     */
    public function testWhichPhpScript()
    {
        $this->expectOutputRegex('~(/php|^$)~');
        Composer::whichPhp();
    }
}
