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
        $this->expectExceptionMessage('\'artifacts\' requires a list of paths');

        new Artifacts(array(null));
    }

    public function provideArtifactsArray()
    {
        return array(
            array(array('build/html/testdox.html')),
            array(array('paths' => array('build/html/testdox.html'))),
        );
    }

    /**
     * @dataProvider provideArtifactsArray
     *
     * @param array $array
     */
    public function testGetPaths(array $array)
    {
        $artifacts = new Artifacts($array);
        $actual = $artifacts->getPaths();
        $expected = array('build/html/testdox.html');
        self::assertSame($expected, $actual);
    }

    public function testCount()
    {
        $array = array('build/html/testdox.html');
        $artifacts = new Artifacts($array);
        self::assertCount(1, $artifacts);
    }
}
