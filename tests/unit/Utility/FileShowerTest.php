<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File;
use PHPUnit\Framework\TestCase;

/**
 * Class FileShowerTest
 *
 * @covers \Ktomk\Pipelines\Utility\FileShower
 */
class FileShowerTest extends TestCase
{
    public function testCreation()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\FileShower', $shower);
    }

    public function testShowImages()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame(0, $shower->showImages());
    }

    public function testShowPipelineIds()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame(0, $shower->showPipelineIds());
    }

    public function testShowPipelines()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame(0, $shower->showPipelines());
    }

    public function testShowPipelinesWithInvalidId()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/invalid-pipeline-id.yml');
        $shower = new FileShower(new Streams(), $file);
        $this->assertSame(1, $shower->showPipelines());
    }

    public function testShowPipelinesWithErrors()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/invalid-pipeline.yml');
        $this->expectOutputRegex('~custom/unit-tests    ERROR     \'image\' invalid Docker image name: \'invalid image\'~');
        $shower = new FileShower(new Streams(null, 'php://output'), $file);
        $this->assertSame(1, $shower->showPipelines());
    }
}
