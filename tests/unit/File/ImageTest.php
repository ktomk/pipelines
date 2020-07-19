<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\File\Image
 */
class ImageTest extends TestCase
{
    public function testCreateFromString()
    {
        $image = new Image('account-name/java:8u66');
        self::assertInstanceOf('Ktomk\Pipelines\File\Image', $image);
    }

    /**
     * @return void
     */
    public function testCreateFromInvalidName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'image\' invalid Docker image name: \'/\'');

        new Image('/');
    }

    public function testCreateFromArray()
    {
        $array = array(
            'name' => 'account-name/java:8u66',
            'username' => '$DOCKER_HUB_USERNAME',
            'password' => '$DOCKER_HUB_PASSWORD',
            'email' => '$DOCKER_HUB_EMAIL',
        );
        $image = new Image($array);
        self::assertInstanceOf('Ktomk\Pipelines\File\Image', $image);
    }

    /**
     */
    public function testCreateFromArrayWithSuperfluousProperties()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('unknown \'image\' property \'superfluous\'');

        $array = array(
            'superfluous' => null,
            'name' => 'account-name/java:8u66',
        );
        $image = new Image($array);
        self::assertInstanceOf('Ktomk\Pipelines\File\Image', $image);
    }

    /**
     */
    public function testCreateFromObject()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'image\' expects either \'a string\' or \'a section\'');

        new Image((object)array());
    }

    /**
     */
    public function testCreateMissingName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'image\' needs a name');

        new Image(array());
    }

    public function testGetName()
    {
        $expected = 'foo/bar:tag';
        $image = new Image($expected);
        self::assertSame($expected, (string)$image->getName());
    }

    public function testNameAsString()
    {
        $expected = 'foo/bar:tag';
        $image = new Image(array('name' => $expected));
        self::assertSame($expected, $image->__toString());
    }

    public function testGetProperties()
    {
        $expected = 'foo/bar:tag';
        $image = new Image(array('name' => $expected));
        $actual = $image->getProperties();
        $class = 'Ktomk\Pipelines\Value\Properties';
        self::assertInstanceOf($class, $actual);
    }

    public function testJsonSerialize()
    {
        $expected = array(
            'name' => 'peace',
        );
        $image = new Image($expected);
        $actual = $image->jsonSerialize();
        self::assertSame(
            $expected,
            $actual
        );
    }

    public function provideImageArrays()
    {
        return array(
            array(array(), false), # no image is not invalid
            array(array('image' => null), true), # null image is invalid
            array(array('image' => ''), true), # empty string image is invalid
            array(array('image' => 'foo'), false), # string image is valid
            array(array('image' => array()), true), # empty array image is invalid
            array(array('image' => array('name' => '')), true), # array image is invalid if it has an empty name
            array(array('image' => array('name' => 'foo')), false), # array image is valid if it has a name
        );
    }

    /**
     * @dataProvider provideImageArrays
     *
     * @param array $array
     * @param $expected
     */
    public function testValidation(array $array, $expected)
    {
        $thrown = false;

        try {
            Image::validate($array);
        } catch (ParseException $exception) {
            $thrown = true;
        }
        self::assertSame($expected, $thrown);
    }

    /**
     * @return void
     */
    public function testValidateImageSectionInvalidName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'image\' invalid Docker image name: \'/\'');

        $image = array(
            'image' => array('name' => '/'),
        );
        Image::validate($image);
    }

    /**
     * @return void
     */
    public function testValidateImageSectionValidName()
    {
        $image = array(
            'image' => array('name' => 'php/5.6:latest'),
        );
        Image::validate($image);
        $this->addToAssertionCount(1);
    }
}
