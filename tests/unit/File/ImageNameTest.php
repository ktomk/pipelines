<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\File\ImageName
 */
class ImageNameTest extends TestCase
{
    public function testCreation()
    {
        $name = new ImageName('foo/bar:latest');
        self::assertInstanceOf('Ktomk\Pipelines\File\ImageName', $name);
    }

    /**
     */
    public function testInvalidImageName()
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        $this->expectExceptionMessage('\'image\' invalid Docker image name: \'foo bar\'');

        $invalid = 'foo bar';
        new ImageName($invalid);
    }

    public function provideDockerImageNames()
    {
        return array(
            array('', false),
            array('php', true),
            array('php:5.6', true),
            array('fedora/httpd:version1.0', true),
            array('my-registry-host:5000/fedora/httpd:version1.0', true),
            array('my registry host:5000/fedora/httpd:version1.0', false),
            array('vendor/group/repo/flavor:tag', true),
            array('/', false),
            array('aws-account-id.dkr.ecr.aws-region.amazonaws.com/java:8u66', true),
        );
    }

    /**
     * @param string $subject
     * @param bool $expected
     * @dataProvider provideDockerImageNames
     */
    public function testImageNameValidation($subject, $expected)
    {
        $actual = ImageName::validate($subject);
        self::assertSame($expected, $actual, $subject);
    }

    public function testToString()
    {
        $expected = 'foo/bar:latest';
        $name = new ImageName($expected);
        self::assertSame($expected, $name->__toString());
        self::assertSame($expected, (string)$name);
    }
}
