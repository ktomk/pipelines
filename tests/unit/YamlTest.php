<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Yaml
 */
class YamlTest extends TestCase
{
    function testFileParsing()
    {
        $path = __DIR__ . '/../../bitbucket-pipelines.yml';

        $struct = Yaml::file($path);

        $this->assertInternalType('array', $struct);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage not a readable file: 'xxx'
     */
    function testCreateFromNonExistentFile()
    {
        Yaml::file("xxx");
    }

    /**
     * Non-YAML files normally parse again to some array
     */
    function testNonYamlFile()
    {
        $array = Yaml::file(__FILE__);

        $this->assertInternalType('array', $array);
    }

    function testYamlNull()
    {
        $array = Yaml::buffer('first: ~');

        $this->assertArrayHasKey('first', $array);
        $this->assertNull($array['first']);
    }
}
