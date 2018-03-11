<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use PHPUnit\Framework\TestCase;

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
}
