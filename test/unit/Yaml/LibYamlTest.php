<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Yaml\LibYaml;
use Ktomk\Pipelines\Yaml\YamlTester;

/**
 * @covers \Ktomk\Pipelines\Yaml\LibYaml
 */
class LibYamlTest extends TestCase
{
    protected function doSetUp()
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped(
                'The YAML extension is not available.'
            );
        }
    }

    public function testCreation()
    {
        self::assertInstanceOf('Ktomk\Pipelines\Yaml\LibYaml', $this->createParser());
    }

    public function testParsing()
    {
        $tester = new YamlTester($this, $this->createParser());

        $tester->assertParser();
    }

    public function testFileParsing()
    {
        $path = __DIR__ . '/../../../bitbucket-pipelines.yml';

        $struct = $this->createParser()->parseFile($path);

        self::assertIsArray($struct);
    }

    /**
     * Non-YAML files normally parse again to some array
     */
    public function testNonYamlFile()
    {
        $array = $this->createParser()->parseFile(__FILE__);

        self::assertNull($array);
    }

    public function testYamlNull()
    {
        $array = $this->createParser()->parseBuffer('first: ~');

        self::assertArrayHasKey('first', $array);
        self::assertNull($array['first']);
    }

    /**
     * @return LibYaml
     */
    private function createParser()
    {
        return new LibYaml();
    }
}
