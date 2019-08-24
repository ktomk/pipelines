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
        $this->assertInstanceOf('Ktomk\Pipelines\File\Image', $image);
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'image' invalid Docker image name: '/'
     */
    public function testCreateFromInvalidName()
    {
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
        $this->assertInstanceOf('Ktomk\Pipelines\File\Image', $image);
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage unknown 'image' property 'superfluous'
     */
    public function testCreateFromArrayWithSuperfluousProperties()
    {
        $array = array(
            'superfluous' => null,
            'name' => 'account-name/java:8u66',
        );
        $image = new Image($array);
        $this->assertInstanceOf('Ktomk\Pipelines\File\Image', $image);
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'image' expects either 'a string' or 'a section'
     */
    public function testCreateFromObject()
    {
        new Image((object)array());
    }

    /**
     * @expectedException \Ktomk\Pipelines\File\ParseException
     * @expectedExceptionMessage 'image' needs a name
     */
    public function testCreateMissingName()
    {
        new Image(array());
    }

    public function testGetName()
    {
        $expected = 'foo/bar:tag';
        $image = new Image($expected);
        $this->assertSame($expected, (string)$image->getName());
    }

    public function testNameAsString()
    {
        $expected = 'foo/bar:tag';
        $image = new Image(array('name' => $expected));
        $this->assertSame($expected, $image->__toString());
    }

    public function testGetProperties()
    {
        $expected = 'foo/bar:tag';
        $image = new Image(array('name' => $expected));
        $actual = $image->getProperties();
        $class = 'Ktomk\Pipelines\Value\Properties';
        $this->assertInstanceOf($class, $actual);
    }

    public function testJsonSerialize()
    {
        $expected = array(
            'name' => 'peace',
        );
        $image = new Image($expected);
        $actual = $image->jsonSerialize();
        $this->assertSame(
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
        $this->assertSame($expected, $thrown);
    }
}
