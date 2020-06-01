<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility\Show;

use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\TestCase;

/**
 * Class FileShowerTest
 *
 * @covers \Ktomk\Pipelines\Utility\Show\FileShower
 * @covers \Ktomk\Pipelines\Utility\Show\FileShowerAbstract
 */
class FileShowerTest extends TestCase
{
    public function testCreation()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\Show\FileShower', $shower);
    }

    public function testShowImages()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame(0, $shower->showImages());
    }

    public function testShowPipelineIds()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame(0, $shower->showPipelineIds());
    }

    public function testShowPipelines()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame(0, $shower->showPipelines());
    }

    public function testShowPipelinesWithInvalidId()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/invalid-pipeline-id.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame(1, $shower->showPipelines());
    }

    public function testShowPipelinesWithErrors()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/invalid-pipeline.yml');
        $this->expectOutputRegex('~custom/unit-tests    ERROR     \'image\' invalid Docker image name: \'invalid image\'~');
        $shower = new FileShower(new Streams(null, 'php://output'), $file);
        $this->assertSame(1, $shower->showPipelines());
    }

    /**
     * @return \string[][]
     */
    public function provideFileForMethod()
    {
        return array(
            array(__DIR__ . '/../../../data/yml/invalid-pipeline.yml', 'showPipelines', 1),
            array(__DIR__ . '/../../../data/yml/invalid-pipeline.yml', 'showFile', 1),
            array(__DIR__ . '/../../../data/yml/invalid-service-definitions.yml', 'showPipelines', 0),
            array(__DIR__ . '/../../../data/yml/invalid-service-definitions.yml', 'showServices', 1),
            array(__DIR__ . '/../../../data/yml/invalid-services.yml', 'showServices', 1),
            array(__DIR__ . '/../../../data/yml/missing-service-definitions.yml', 'showServices', 1),
            array(__DIR__ . '/../../../data/yml/invalid-pipeline.yml', 'showServices', 1),
            array(__DIR__ . '/../../../../bitbucket-pipelines.yml', 'showFile', 0),
            array(__DIR__ . '/../../../../bitbucket-pipelines.yml', 'showImages', 0),
            array(__DIR__ . '/../../../../bitbucket-pipelines.yml', 'showServices', 0),
        );
    }

    /**
     * @dataProvider provideFileForMethod
     *
     * @param string $path
     * @param string $method file-shower method name
     * @param int $expected
     */
    public function testShowFileByMethod($path, $method, $expected)
    {
        $file = File::createFromFile($path);
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame($expected, $shower->{$method}());
    }
}
