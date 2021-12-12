<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility\Show;

use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\TestCase;

/**
 * Class FileShowerTest
 *
 * @covers \Ktomk\Pipelines\Utility\Show\FileShower
 * @covers \Ktomk\Pipelines\Utility\Show\FileShowerAbstract
 * @covers \Ktomk\Pipelines\Utility\Show\FileTable
 */
class FileShowerTest extends TestCase
{
    public function testCreation()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\Show\FileShower', $shower);
    }

    public function testShowImages()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        self::assertSame(0, $shower->showImages());
    }

    public function testShowPipelineIds()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        self::assertSame(0, $shower->showPipelineIds());
    }

    public function testShowPipelines()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/bitbucket-pipelines.yml');
        $shower = new FileShower(new Streams(), $file);
        self::assertSame(0, $shower->showPipelines());
    }

    public function testShowPipelinesWithInvalidId()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/invalid/pipeline-id.yml');
        $shower = new FileShower(new Streams(), $file);
        self::assertSame(1, $shower->showPipelines());
    }

    public function testShowPipelinesWithErrors()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/invalid/pipeline.yml');
        $this->expectOutputRegex('~custom/unit-tests    ERROR     \'image\' invalid Docker image name: \'invalid image\'~');
        $shower = new FileShower(new Streams(null, 'php://output'), $file);
        self::assertSame(1, $shower->showPipelines());
    }

    /**
     * @return \string[][]
     */
    public function provideFileForMethod()
    {
        return array(
            array(__DIR__ . '/../../../data/yml/invalid/artifacts.yml', 'showPipelines', 1),
            array(__DIR__ . '/../../../data/yml/invalid/artifacts.yml', 'showFile', 1),
            array(__DIR__ . '/../../../data/yml/invalid/caches.yml', 'showFile', 1, "file parse error: cache 'borked' must reference a custom or default cache definition"),
            array(__DIR__ . '/../../../data/yml/invalid/pipeline.yml', 'showPipelines', 1),
            array(__DIR__ . '/../../../data/yml/invalid/pipeline.yml', 'showFile', 1),
            array(__DIR__ . '/../../../data/yml/invalid/service-definitions.yml', 'showPipelines', 0),
            array(__DIR__ . '/../../../data/yml/invalid/service-definitions.yml', 'showServices', 1),
            array(__DIR__ . '/../../../data/yml/invalid/services.yml', 'showServices', 1),
            array(__DIR__ . '/../../../data/yml/invalid/service-definitions-missing.yml', 'showServices', 1),
            array(__DIR__ . '/../../../data/yml/invalid/pipeline.yml', 'showServices', 1),
            array(__DIR__ . '/../../../../bitbucket-pipelines.yml', 'showFile', 0),
            array(__DIR__ . '/../../../../bitbucket-pipelines.yml', 'showImages', 0),
            array(__DIR__ . '/../../../../bitbucket-pipelines.yml', 'showServices', 0),
            array(__DIR__ . '/../../../data/yml/cache.yml', 'showFile', 0),
            array(__DIR__ . '/../../../data/yml/steps.yml', 'showFile', 0),
            array(__DIR__ . '/../../../data/yml/steps.yml', 'showPipelines', 0),
        );
    }

    /**
     * @dataProvider provideFileForMethod
     *
     * @covers \Ktomk\Pipelines\File\Info\StepInfo::annotate
     * @covers \Ktomk\Pipelines\File\Info\StepsInfo::getSummary
     *
     * @param string $path
     * @param string $method file-shower method name
     * @param int $expected
     * @param null|string $parseExceptionMessage
     */
    public function testShowFileByMethod($path, $method, $expected, $parseExceptionMessage = null)
    {
        self::assertTrue(method_exists(__NAMESPACE__ . '\FileShower', $method), "FileShower::${method}()");
        $file = File::createFromFile($path);
        $shower = new FileShower(new Streams(), $file);

        if (null !== $parseExceptionMessage) {
            $this->expectException('Ktomk\Pipelines\File\ParseException');
            $this->expectExceptionMessage($parseExceptionMessage);
        }

        /** @see FileShower::showPipelines */
        /** @see FileShower::showFile */
        /** @see FileShower::showServices */
        /** @see FileShower::showImages */
        self::assertSame($expected, $shower->{$method}());
    }

    public function provideHappyFilesForShowFileMethod()
    {
        $stepsFile = __DIR__ . '/../../../data/yml/steps.yml';
        $conditionFile = __DIR__ . '/../../../data/yml/condition.yml';

        return array(
            'steps.manual-trigger-annotation'  => array(
                $stepsFile,
                'showFile',
                <<<'TEXT'
PIPELINE ID    STEP    IMAGE                      NAME
default        1       ktomk/pipelines:busybox    "step #1"
default        2       ktomk/pipelines:busybox    "step #2"
default        3       ktomk/pipelines:busybox    "step #3"
default        4 *M    ktomk/pipelines:busybox    no-name
TEXT
                ,
            ),
            'steps.manual-trigger-after-name-annotation' => array(
                $stepsFile,
                'showPipelines',
                <<<'TEXT'
PIPELINE ID    IMAGES                     STEPS
default        ktomk/pipelines:busybox    4 ("step #1"; "step #2"; "step #3"; no-name *M)
TEXT
                ,
            ),
            'condition.condition-annotation' => array(
                $conditionFile,
                'showFile',
                <<<'TEXT'
PIPELINE ID    STEP    IMAGE                      NAME
default        1 *C    ktomk/pipelines:busybox    no-name
TEXT
                ,
            ),
            'condition.condition-after-name-annotation' => array(
                $conditionFile,
                'showPipelines',
                <<<'TEXT'
PIPELINE ID    IMAGES                     STEPS
default        ktomk/pipelines:busybox    1 (no-name *C)
TEXT
                ,
            ),
        );
    }

    /**
     * @covers \Ktomk\Pipelines\File\Info\StepInfo
     * @covers \Ktomk\Pipelines\File\Info\StepsInfo
     * @covers \Ktomk\Pipelines\File\Info\StepsStepInfoIterator
     *
     * @dataProvider provideHappyFilesForShowFileMethod
     *
     * @param string $path
     * @param string $method
     * @param string $expected
     *
     * @return void
     */
    public function testShowFileByMethodHappyOutput($path, $method, $expected)
    {
        self::assertTrue(method_exists(__NAMESPACE__ . '\FileShower', $method), "FileShower::${method}()");
        $expected = rtrim($expected) . "\n";

        list($outHandle) = LibTmp::tmpFile();
        $shower = new FileShower(new Streams(null, $outHandle), File::createFromFile($path));
        self::assertSame(0, $shower->{$method}(), 'error-free');
        rewind($outHandle);
        $actual = stream_get_contents($outHandle);

        self::assertSame($expected, $actual);
    }
}
