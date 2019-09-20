<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Yaml\Yaml
 */
class YamlTest extends TestCase
{
    public function testFileParsing()
    {
        $path = __DIR__ . '/../../../bitbucket-pipelines.yml';

        $struct = Yaml::file($path);

        $this->assertInternalType('array', $struct);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage not a readable file: 'xxx'
     */
    public function testCreateFromNonExistentFile()
    {
        Yaml::file('xxx');
    }

    /**
     * Non-YAML files normally parse again to some array
     */
    public function testNonYamlFile()
    {
        $array = Yaml::file(__FILE__);

        $this->assertNull($array);
    }

    public function testYamlNull()
    {
        $array = Yaml::buffer('first: ~');

        $this->assertArrayHasKey('first', $array);
        $this->assertNull($array['first']);
    }
}
