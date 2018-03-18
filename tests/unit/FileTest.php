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
    public function testCreateFromFile()
    {
        $path = __DIR__ . '/../../' . File::FILE_NAME;

        $file = File::createFromFile($path);

        $this->assertNotNull($file);

        return $file;
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage /error.yml; verify the file contains valid YAML
     */
    public function testCreateFromFileWithError()
    {
        $path = __DIR__ . '/../data/error.yml';

        File::createFromFile($path);
    }

    /**
     * @return File
     */
    public function testCreateFromFileWithInvalidId()
    {
        $path = __DIR__ . '/../data/invalid-pipeline-id.yml';

        $file = File::createFromFile($path);

        $this->assertNotNull($file);

        return $file;
    }

    /**
     * @param File $file
     * @depends testCreateFromFileWithInvalidId
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage file parse error: invalid pipeline id '
     */
    public function testGetPipelinesWithInvalidIdParseError(File $file)
    {
        $file->getPipelines();
    }

    /**
     * @depends testCreateFromFile
     * @param File $file
     */
    public function testGetImage(File $file)
    {
        $image = $file->getImage();
        $this->assertInstanceOf('Ktomk\Pipelines\File\Image', $image);
        $imageString = (string)$image;
        $this->assertInternalType('string', $imageString);
        $expectedImage = File::DEFAULT_IMAGE;
        $this->assertSame($expectedImage, $imageString);
    }

    public function testGetImageSet()
    {
        $expected = 'php:5.6';
        $image = array(
            'image' => $expected,
            'pipelines' => array('tags' => array()),
        );
        $file = new File($image);
        $this->assertSame($expected, (string)$file->getImage());
    }

    public function testMinimalFileStructureAndDefaultValues()
    {
        $minimal = array(
            'pipelines' => array('tags' => array()),
        );

        $file = new File($minimal);

        $this->assertSame(File::DEFAULT_IMAGE, (string)$file->getImage());
        $this->assertSame(File::DEFAULT_CLONE, $file->getClone());

        $steps = $file->getDefault();
        $this->assertNull($steps);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing required property 'pipelines'
     */
    public function testMissingPipelineException()
    {
        new File(array());
    }

    public function testClone()
    {
        $file = new File(array(
            'clone' => 666,
            'pipelines' => array('default' => array()),
        ));
        $this->assertSame(666, $file->getClone());
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
        $this->assertInstanceOf('Ktomk\Pipelines\Pipeline', $pipeline);
        $steps = $pipeline->getSteps();
        $this->assertArrayHasKey(0, $steps);
        $this->assertInstanceOf('Ktomk\Pipelines\Step', $steps[0]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage 'image' requires a Docker image name
     */
    public function testImageNameRequired()
    {
        new File(
            array(
                'image' => null,
                'pipelines' => array(),
            )
        );
    }

    public function testGetPipelineIds()
    {
        $file = File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml');
        $ids = $file->getPipelineIds();
        $this->assertInternalType('array', $ids);
        $this->assertArrayHasKey(11, $ids);
        $this->assertSame('custom/unit-tests', $ids[11]);
    }

    /**
     * @depends testCreateFromFile
     * @param File $file
     */
    public function testGetPipelines(File $file)
    {
        $actual = $file->getPipelines();
        $this->assertGreaterThan(1, count($actual));
        $this->assertContainsOnlyInstancesOf(
            'Ktomk\Pipelines\Pipeline',
            $actual
        );
    }

    public function testGetReference()
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
    public function testFlyweightPatternWithPatternSection()
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
    public function testInvalidReferenceName()
    {
        File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml')
            ->getById('branch/master'); # must be branch_es_
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage section
     */
    public function testNoSectionException()
    {
        new File(array('pipelines' => array()));
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'default' requires a list of steps
     */
    public function testNoListInSectionException()
    {
        new File(array('pipelines' => array('default' => 1)));
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'branches' requires a list
     */
    public function testNoListInBranchesSectionException()
    {
        new File(array('pipelines' => array('branches' => 1)));
    }

    public function testSearchReference()
    {
        $file = File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml');

        $pipeline = $file->searchTypeReference('branches', 'master');
        $this->asPlFiStName('master duplicate', $pipeline, 'direct match');

        $pipeline = $file->searchTypeReference('branches', 'my/feature');
        $this->asPlFiStName('*/feature', $pipeline);
    }

    public function testDefaultAsFallBack()
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

    public function testNoDefaultAsFallBack()
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
    public function testSearchReferenceInvalidScopeException()
    {
        $file = File::createFromFile(__DIR__ . '/../data/bitbucket-pipelines.yml');
        $file->searchTypeReference('invalid', '');
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage custom/0: named pipeline required
     */
    public function testParseErrorOnGetById()
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

    public function testGetIdFrom()
    {
        $file = new File(array('pipelines' => array('default' => array(
            array('step' => array('script' => array(':')))
        ))));
        $pipeline = $file->getById('default');
        $this->assertNotNull($pipeline);
        $actual = $file->getIdFrom($pipeline);
        $this->assertSame('default', $actual);
    }

    public function testGetIdFromNonFilePipeline()
    {
        $file = new File(array('pipelines' => array('default' => array(
            array('step' => array('script' => array(':')))
        ))));

        $pipeline = new Pipeline($file, array(array('step' => array('script' => array(':')))));
        $this->assertNull($file->getIdFrom($pipeline));
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage invalid Docker image name
     */
    public function testInvalidImageName()
    {
        new File(array(
            'image' => 'php:5.6find . -name .libs -a -type d|xargs rm -rf',
            'pipelines' => array('default' => array()),
        ));
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException :
     * @expectedExceptionMessage 'image' invalid Docker image name: '/'
     */
    public function testValidateImageSectionInvalidName()
    {
        $image = array(
            'image' => array('name' => '/'),
        );
        File::validateImage($image);
    }

    public function testValidateImageSectionValidName()
    {
        $image = array(
            'image' => array('name' => 'php/5.6:latest'),
        );
        File::validateImage($image);
        $this->addToAssertionCount(1);
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
        $this->assertSame($expected, $first->getName(), $message);
    }
}
