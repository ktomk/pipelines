<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use PHPUnit\Framework\TestCase;

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
        $this->assertInstanceOf(
            'Ktomk\Pipelines\File\Artifacts',
            $artifacts
        );
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'artifacts' requires a list
     */
    public function testCreationWithNonArray()
    {
        new Artifacts(null);
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'artifacts' requires a list of strings, #0 is not a string
     */
    public function testCreationWithIncompleteList()
    {
        new Artifacts(array(null));
    }

    public function testGetPatterns()
    {
        $array = array('build/html/testdox.html');
        $artifacts = new Artifacts($array);
        $actual = $artifacts->getPatterns();
        $this->assertSame($array, $actual);
    }
}
