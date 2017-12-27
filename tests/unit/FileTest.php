<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Runner\Reference;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\File
 */
class FileTest extends TestCase
{
    function testCreateFromFile()
    {
        $path = __DIR__ . '/../../' . File::FILE_NAME;

        $file = File::createFromFile($path);

        $this->assertNotNull($file);

        return $file;
    }

    /**
     * @depends testCreateFromFile
     * @param File $file
     */
    function testGetImage(File $file)
    {
        $image = $file->getImage();
        $this->assertInternalType('string', $image);
        $expectedImage = File::DEFAULT_IMAGE;
        $this->assertEquals($expectedImage, $image);
    }

    function testGetImageSet()
    {
        $expected = 'php:5.6';
        $image = array(
            'image' => $expected,
            'pipelines' => array('tags' => array()),
        );
        $file = new File($image);
        $this->assertSame($expected, $file->getImage());
    }


    function testMinimalFileStructureAndDefaultValues()
    {
        $minimal = array(
            'pipelines' => array('tags' => array()),
        );

        $file = new File($minimal);

        $this->assertSame(File::DEFAULT_IMAGE, $file->getImage());
        $this->assertSame(File::DEFAULT_CLONE, $file->getClone());

        $steps = $file->getDefault();
        $this->assertNull($steps);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing required property 'pipelines'
     */
    function testMissingPipelineException()
    {
        new File(array());
    }

    function testClone()
    {
        $file = new File(array(
            'clone' => 666,
            'pipelines' => array('default' => array()),
        ));
        $this->assertSame(666, $file->getClone());
    }

    function testDefaultPipeline()
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
        $this->assertInstanceOf('Ktomk\Pipelines\Pipeline', $pipeline);
        $steps = $pipeline->getSteps();
        $this->assertArrayHasKey(0, $steps);
        $this->assertInstanceOf('Ktomk\Pipelines\Step', $steps[0]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage 'image' requires a Docker image name
     */
    function testImageNameRequired()
    {
         new File(
            array(
                'image' => null,
                'pipelines' => array(),
            )
        );
    }

    function testGetPipilineIds()
    {
        $file = File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml');
        $ids = $file->getPipelineIds();
        $this->assertInternalType('array', $ids);
        $this->assertArrayHasKey(11, $ids);
        $this->assertSame('custom/unit-tests', $ids[11]);
    }

    function testGetReference()
    {
        $file = File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml');

        $pipeline = $file->getById('branches/master');
        $this->assertNotNull($pipeline);

        # test instance count
        $default = $file->getById('default');
        $this->assertSame($default, $file->getDefault());
    }

    /**
     * test that in the internal file array, the pipelines
     * data gets referenced to the concrete pipeline object
     * when it once hast been acquired.
     */
    function testFlyweightPatternWithPatternSection()
    {
        $withBranch = array(
            'pipelines' => array(
                'branches' => array(
                    "master" => array(array('step' => array(
                        'name' => 'master branch',
                        'script' => array("1st line"),
                    )))
                ),
            ),
        );
        $file = new File($withBranch);

        $pipeline = $file->searchTypeReference('branches', 'master');
        $this->asPlFiStName('master branch', $pipeline);

        $refl = new \ReflectionObject($file);
        $prop = $refl->getProperty('array');
        $prop->setAccessible(true);
        $array = $prop->getValue($file);
        $actual = $array['pipelines']['branches']['master'];
        $this->assertSame($pipeline, $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid id 'branch/master'
     */
    function testInvalidReferenceName()
    {
        File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml')
            ->getById('branch/master'); # must be branch_es_
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage section
     */
    function testNoSectionException()
    {
        new File(array('pipelines' => array()));
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'default' requires a list of steps
     */
    function testNoListInSectionException()
    {
        new File(array('pipelines' => array('default' => 1)));
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'branches' requires a list
     */
    function testNoListInBranchesSectionException()
    {
        new File(array('pipelines' => array('branches' => 1)));
    }

    function testSearchReference()
    {
        $file = File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml');

        $pipeline = $file->searchTypeReference('branches', 'master');
        $this->asPlFiStName('master duplicate', $pipeline, 'direct match');

        $pipeline = $file->searchTypeReference('branches', 'my/feature');
        $this->asPlFiStName('*/feature', $pipeline);
    }

    function testDefaultAsFallBack()
    {
        $withDefault = array(
            'pipelines' => array(
                'default' => array(
                    array('step' => array(
                        'name' => 'default',
                        'script' => array("1st line"),
                    )),
                ),
                'branches' => array(
                    "master" => array(array('step' => array(
                        'name' => 'master branch',
                        'script' => array("1st line"),
                    )))
                ),
            ),
        );
        $file = new File($withDefault);

        $reference = Reference::create('bookmark:xy');
        $pipeline = $file->searchReference($reference);
        $this->asPlFiStName('default', $pipeline);

        $pipeline = $file->searchTypeReference('bookmarks', 'xy');
        $this->asPlFiStName('default', $pipeline);


        $pipeline = $file->searchTypeReference('branches', 'feature/xy');
        $this->asPlFiStName('default', $pipeline);
    }

    function testNoDefaultAsFallBack()
    {
        $withoutDefault = array(
            'pipelines' => array(
                'branches' => array(
                    "master" => array(array('step' => array(
                        'name' => 'master branch',
                        'script' => array("1st line"),
                    )))
                ),
            ),
        );
        $file = new File($withoutDefault);

        $this->assertNull($file->getIdDefault());
        $this->assertNull($file->getDefault());

        $reference = Reference::create();
        $pipeline = $file->searchReference($reference);
        $this->assertNull($pipeline);

        $reference = Reference::create();
        $pipeline = $file->searchIdByReference($reference);
        $this->assertNull($pipeline);

        $reference = Reference::create('bookmark:xy');
        $pipeline = $file->searchIdByReference($reference);
        $this->assertNull($pipeline);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid type 'invalid'
     */
    function testSearchReferenceInvalidScopeException()
    {
        $file = File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml');
        $file->searchTypeReference('invalid', '');
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage custom/0: named pipeline required
     */
    function testParseErrorOnGetById()
    {
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
        $this->assertEquals($expected, $first->getName(), $message);
    }
}
