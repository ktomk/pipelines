<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\File\Options
 */
class OptionsTest extends TestCase
{
    public function testCreation()
    {
        new Options(array());
        $this->addToAssertionCount(1);
    }

    public function testDefaultDockerIsFalse()
    {
        $options = new Options(array());
        self::assertFalse($options->getDocker());
    }

    public function testNonBooleanDockerThrows()
    {
        $this->expectException(__NAMESPACE__ . '\ParseException');
        $this->expectExceptionMessage("file parse error: global option 'docker' should be a boolean, it is currently defined string");
        new Options(array('docker' => 'fake'));
    }
}
