<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\TestCase;

/**
 * Class ArtifactsTest
 *
 * @covers \Ktomk\Pipelines\File\Artifacts
 */
class ArtifactsTest extends TestCase
{
    public function testCreation()
    {
        $array = array('build/html/testdox.html');
        $artifacts = new Artifacts($array);
        self::assertInstanceOf(
            'Ktomk\Pipelines\File\Artifacts',
            $artifacts
        );
    }

    /**
     */
    public function testCreationWithEmptyArray()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'artifacts\' requires a list');

        new Artifacts(array());
    }

    /**
     */
    public function testCreationWithIncompleteList()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'artifacts\' requires a list of strings, #0 is not a string');

        new Artifacts(array(null));
    }

    public function testGetPatterns()
    {
        $array = array('build/html/testdox.html');
        $artifacts = new Artifacts($array);
        $actual = $artifacts->getPatterns();
        self::assertSame($array, $actual);
    }

    public function testCount()
    {
        $array = array('build/html/testdox.html');
        $artifacts = new Artifacts($array);
        self::assertCount(1, $artifacts);
    }
}
