<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\Runner\Reference;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\File\File
 */
class FileTest extends TestCase
{
    public function testCreateFromDefaultFile()
    {
        $path = __DIR__ . '/../../../' . File::FILE_NAME;

        $file = File::createFromFile($path);

        self::assertNotNull($file);

        return $file;
    }

    public function provideWorkingYmlFiles()
    {
        $dir = __DIR__ . '/../../data/yml';

        return array(
            array($dir . '/alias.yml'),
            array($dir . '/alias2.yml'),
            array($dir . '/bitbucket-pipelines.yml'),
            array($dir . '/cache.yml'),
            array($dir . '/images.yml'),
            array($dir . '/no-default-pipeline.yml'),
            array($dir . '/steps.yml'),
            array($dir . '/pull-requests-pipeline.yml'),
        );
    }

    /**
     * @dataProvider provideWorkingYmlFiles
     *
     * @param string $path
     */
    public function testCreateFromFile($path)
    {
        $file = File::createFromFile($path);
        self::assertNotNull($file);
        self::assertNotNull($file->getPath());
        self::assertSame($path, $file->getPath());
    }

    public function testCreateFromPipeFile()
    {
        $path = __DIR__ . '/../../data/yml/pipe.yml';
        $file = File::createFromFile($path);
        self::assertNotNull($file);
        self::assertSame($path, $file->getPath());

        $default = $file->getById('branches/develop');
        self::assertNotNull($default);
    }

    /**
     */
    public function testCreateFromFileWithError()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('/error.yml; verify the file contains valid YAML');

        $path = __DIR__ . '/../../data/yml/yaml/error.yml';

        File::createFromFile($path);
    }

    /**
     * @return File
     */
    public function testCreateFromFileWithInvalidId()
    {
        $path = __DIR__ . '/../../data/yml/invalid/pipeline-id.yml';

        $file = File::createFromFile($path);

        self::assertNotNull($file);

        return $file;
    }

    /**
     * @depends testCreateFromDefaultFile
     *
     * @param File $file
     */
    public function testSearchIdByReference(File $file)
    {
        self::assertSame('default', $file->searchIdByReference(Reference::create()));
    }

    /**
     * @depends testCreateFromDefaultFile
     *
     * @param File $file
     */
    public function testGetImage(File $file)
    {
        $image = $file->getImage();
        self::assertInstanceOf('Ktomk\Pipelines\File\Image', $image);
        $imageString = (string)$image;
        self::assertIsString($imageString);
        $expectedImage = File::DEFAULT_IMAGE;
        self::assertSame($expectedImage, $imageString);
    }

    public function testGetImageSet()
    {
        $expected = 'php:5.6';
        $image = array(
            'image' => $expected,
            'pipelines' => array('tags' => array()),
        );
        $file = new File($image);
        self::assertSame($expected, (string)$file->getImage());
    }

    public function testMinimalFileStructureAndDefaultValues()
    {
        $minimal = array(
            'pipelines' => array('tags' => array()),
        );

        $file = new File($minimal);

        self::assertSame(File::DEFAULT_IMAGE, (string)$file->getImage());
        self::assertSame(File::DEFAULT_CLONE, $file->getClone());

        $steps = $file->getDefault();
        self::assertNull($steps);
    }

    /**
     */
    public function testMissingPipelineException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Missing required property \'pipelines\'');

        new File(array());
    }

    public function testClone()
    {
        $file = new File(array(
            'clone' => 666,
            'pipelines' => array('default' => array()),
        ));
        self::assertSame(666, $file->getClone());
    }

    public function testDefaultPipeline()
    {
        $default = array(
            'pipelines' => array(
                'default' => array(
                    array(
                        'step' => array(
                            'script' => array(
                                'echo "hello world"; echo $?',
                            ),
                        ),
                    ),
                ),
            ),
        );

        $file = new File($default);
        $pipeline = $file->getDefault();
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline', $pipeline);
        $steps = $pipeline->getSteps();
        self::assertArrayHasKey(0, $steps);
        self::assertInstanceOf('Ktomk\Pipelines\File\Pipeline\Step', $steps[0]);
    }

    /**
     */
    public function testImageNameRequired()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('\'image\' requires a Docker image name');

        new File(
            array(
                'image' => null,
                'pipelines' => array(),
            )
        );
    }

    /**
     * @depends testCreateFromDefaultFile
     *
     * @param File $file
     */
    public function testGetPipelines(File $file)
    {
        $actual = $file->getPipelines();

        self::assertInstanceOf(
            'Ktomk\Pipelines\File\Pipelines',
            $actual
        );
    }

    /**
     * @return void
     */
    public function testInvalidImageName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('invalid Docker image name');

        new File(array(
            'image' => 'php:5.6find . -name .libs -a -type d|xargs rm -rf',
            'pipelines' => array('default' => array()),
        ));
    }

    /**
     * definitions is always available typed
     *
     * @depends testCreateFromDefaultFile
     *
     * @param File $file
     */
    public function testGetDefinitions(File $file)
    {
        self::assertInstanceOf('Ktomk\Pipelines\File\Definitions', $file->getDefinitions());
    }

    /**
     * definitions is always available typed
     *
     * @depends testCreateFromDefaultFile
     *
     * @param File $file
     */
    public function testGetOptions(File $file)
    {
        self::assertInstanceOf('Ktomk\Pipelines\File\Options', $file->getOptions());
    }

    /**
     * definitions is always available typed
     *
     * @depends testCreateFromDefaultFile
     *
     * @param File $file
     */
    public function testGetArray(File $file)
    {
        self::assertIsArray($file->getArray());
    }
}
