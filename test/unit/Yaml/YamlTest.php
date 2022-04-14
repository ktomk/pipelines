<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Yaml\Yaml
 */
class YamlTest extends TestCase
{
    protected function doTearDown()
    {
        Yaml::$classes = array();
        parent::doTearDown();
    }

    public function testFileParsing()
    {
        $path = __DIR__ . '/../../../bitbucket-pipelines.yml';

        $struct = Yaml::file($path);

        self::assertIsArray($struct);
    }

    /**
     */
    public function testCreateFromNonExistentFile()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('not a readable file: \'xxx\'');

        Yaml::file('xxx');
    }

    /**
     * Non-YAML files normally parse again to some array
     */
    public function testNonYamlFile()
    {
        $array = Yaml::tryFile(__FILE__);

        self::assertNull($array);
    }

    public function testYamlNull()
    {
        $array = Yaml::buffer('first: ~');

        self::assertArrayHasKey('first', $array);
        self::assertNull($array['first']);
    }

    public function testNoParserAvailable()
    {
        Yaml::$classes = array('');

        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage('No YAML parser available');

        Yaml::buffer('---');
    }
}
