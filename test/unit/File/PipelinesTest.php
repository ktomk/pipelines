<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\Runner\Reference;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Yaml\Yaml;
use ReflectionException;
use ReflectionObject;

/**
 * Class PipelinesTest
 *
 * @package Ktomk\Pipelines\File
 * @covers \Ktomk\Pipelines\File\Pipelines
 */
class PipelinesTest extends TestCase
{
    /**
     * @return Pipelines
     */
    public function testCreateFromDefaultFile()
    {
        $path = __DIR__ . '/../../../' . File::FILE_NAME;

        $result = Yaml::file($path);

        self::assertArrayHasKey('pipelines', $result, 'file fixture broken');
        self::assertIsArray($result['pipelines'], 'file fixture broken');

        $pipelines = new Pipelines($result['pipelines'], $this->createMock('Ktomk\Pipelines\File\File'));

        self::assertInstanceOf('Ktomk\Pipelines\File\Pipelines', $pipelines);

        return $pipelines;
    }

    /**
     * @depends testCreateFromDefaultFile
     *
     * @param Pipelines $pipelines
     *
     * @return void
     */
    public function testGetPipelines(Pipelines $pipelines)
    {
        $actual = $pipelines->getPipelines();
        self::assertGreaterThan(1, count($actual));
        self::assertContainsOnlyInstancesOf(
            'Ktomk\Pipelines\File\Pipeline',
            $actual
        );
    }

    /**
     * @return array
     */
    public function provideParseErrors()
    {
        return array(
            array(array(), "'pipelines' requires at least a default,"),
            array(
                array('default' => null),
                "'pipelines' requires at least a default,",
            ),
            array(
                array('default' => 'foo'),
                "'default' requires a list of steps",
            ),
            array(
                array('branches' => 'foo'),
                "'branches' requires a list",
            ),
        );
    }

    /**
     * @dataProvider provideParseErrors
     *
     * @param array $array
     * @param string $message
     */
    public function testParseErrors(array $array, $message)
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage($message);

        new Pipelines($array);
    }

    /**
     * @return void
     */
    public function testGetFileUnassociated()
    {
        $pipelines = new Pipelines($this->getMinimalArray(), $mock = $this->createMock('Ktomk\Pipelines\File\File'));
        self::assertSame($mock, $pipelines->getFile());

        $pipelines = new Pipelines($this->getMinimalArray());
        self::assertNull($pipelines->getFile());
    }

    /**
     * test that in the internal file array, the pipelines
     * data gets referenced to the concrete pipeline object
     * when it once hast been acquired.
     *
     * @throws ReflectionException
     *
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testFlyweightPatternWithPatternSection()
    {
        $withBranch = array(
            'branches' => array(
                'master' => array(array('step' => array(
                    'name' => 'master branch',
                    'script' => array('1st line'),
                ))),
            ),
        );
        $file = $this->createMock('Ktomk\Pipelines\File\File');
        $pipelines = new Pipelines($withBranch, $file);

        $pipeline = $pipelines->searchTypeReference('branches', 'master');
        $this->asPlFiStName('master branch', $pipeline);

        $reflection = new ReflectionObject($pipelines);
        $prop = $reflection->getProperty('array');
        $prop->setAccessible(true);
        $array = $prop->getValue($pipelines);
        $actual = $array['branches']['master'];
        self::assertSame($pipeline, $actual);
    }

    public function testGetReference()
    {
        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');

        $pipeline = $file->getById('branches/master');
        self::assertNotNull($pipeline);

        # test instance count
        $default = $file->getById('default');
        self::assertSame($default, $file->getDefault());
    }

    public function testGetPipelineIds()
    {
        $pipelines = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml')->getPipelines();
        $ids = $pipelines->getPipelineIds();
        self::assertIsArray($ids);
        self::assertArrayHasKey(12, $ids);
        self::assertSame('custom/unit-tests', $ids[12]);
    }

    /**
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testSearchReference()
    {
        $pipelines = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml')->getPipelines();

        $pipeline = $pipelines->searchTypeReference('branches', 'master');
        $this->asPlFiStName('master duplicate', $pipeline, 'direct match');

        $pipeline = $pipelines->searchTypeReference('branches', 'my/feature');
        $this->asPlFiStName('*/feature', $pipeline);
    }

    /**
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testDefaultAsFallBack()
    {
        $withDefault = array(
            'default' => array(
                array('step' => array(
                    'name' => 'default',
                    'script' => array('1st line'),
                )),
            ),
            'branches' => array(
                'master' => array(array('step' => array(
                    'name' => 'master branch',
                    'script' => array('1st line'),
                ))),
            ),
        );
        $pipelines = new Pipelines($withDefault, $this->createMock('Ktomk\Pipelines\File\File'));

        $reference = Reference::create('bookmark:xy');
        $pipeline = $pipelines->searchReference($reference);
        $this->asPlFiStName('default', $pipeline);

        $pipeline = $pipelines->searchTypeReference('bookmarks', 'xy');
        $this->asPlFiStName('default', $pipeline);

        $pipeline = $pipelines->searchTypeReference('branches', 'feature/xy');
        $this->asPlFiStName('default', $pipeline);
    }

    /**
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testNoDefaultAsFallBack()
    {
        $withoutDefault = array(
            'branches' => array(
                'master' => array(array('step' => array(
                    'name' => 'master branch',
                    'script' => array('1st line'),
                ))),
            ),
        );
        $pipelines = new Pipelines($withoutDefault);

        self::assertNull($pipelines->getIdDefault());
        self::assertNull($pipelines->getDefault());

        $reference = Reference::create();
        $pipeline = $pipelines->searchReference($reference);
        self::assertNull($pipeline);

        $reference = Reference::create();
        $pipeline = $pipelines->searchIdByReference($reference);
        self::assertNull($pipeline);

        $reference = Reference::create('bookmark:xy');
        $pipeline = $pipelines->searchIdByReference($reference);
        self::assertNull($pipeline);
    }

    /**
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testSearchReferenceInvalidScopeException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid type \'invalid\'');

        $file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
        $file->getPipelines()->searchTypeReference('invalid', '');
    }

    /**
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testParseErrorOnGetById()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('custom/0: named pipeline required');

        $file = new File(array(
            'pipelines' => array(
                'custom' => array(
                    'void',
                ),
            ),
        ));
        $file->getById('custom/0');
    }

    /**
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testGetIdOfPipeline()
    {
        $file = new File(array('pipelines' => array('default' => array(
            array('step' => array('script' => array(':'))),
        ))));
        $pipelines = $file->getPipelines();
        $pipeline = $pipelines->getById('default');
        self::assertNotNull($pipeline);
        $actual = $pipelines->getId($pipeline);
        self::assertSame('default', $actual);
    }

    /**
     * An non-associated pipeline in a pipelines object can't be obtained
     * an id from
     *
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testGetIdOfNonFilePipelineReturnsNull()
    {
        $file = new File(array('pipelines' => array('default' => array(
            array('step' => array('script' => array(':'))),
        ))));
        $pipelines = $file->getPipelines();

        $pipeline = new Pipeline($file, array(array('step' => array('script' => array(':')))));
        self::assertNull($pipelines->getId($pipeline));
    }

    /**
     * @covers \Ktomk\Pipelines\File\PipelinesReferences
     */
    public function testInvalidReferenceName()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid id \'branch/master\'');

        File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml')
            ->getById('branch/master'); # must be branch_es_
    }

    /**
     * @return void
     */
    public function testGetPipelinesWithInvalidIdParseError()
    {
        $path = __DIR__ . '/../../data/yml/invalid/pipeline-id.yml';

        $file = File::createFromFile($path);

        self::assertNotNull($file);

        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('file parse error: invalid pipeline id \'');

        $file->getPipelines()->getPipelines();
    }

    /**
     * pipelines fixture
     *
     * @return array
     */
    private function getMinimalArray()
    {
        return array('default' => array());
    }

    /**
     * assertPipelineFirstStepName
     *
     * @param string $expected
     * @param Pipeline $pipeline
     * @param string $message [optional]
     */
    private function asPlFiStName($expected, Pipeline $pipeline, $message = '')
    {
        $steps = $pipeline->getSteps();
        $first = $steps[0];
        self::assertSame($expected, $first->getName(), $message);
    }
}
