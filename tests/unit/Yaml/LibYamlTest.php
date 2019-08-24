<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Yaml\LibYaml;
use Ktomk\Pipelines\Yaml\YamlTester;

/**
 * @covers \Ktomk\Pipelines\Yaml\LibYaml
 */
class LibYamlTest extends TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('yaml')) {
            $this->markTestSkipped(
                'The YAML extension is not available.'
            );
        }
    }

    public function testCreation()
    {
        $this->assertInstanceOf('Ktomk\Pipelines\Yaml\LibYaml', $this->createParser());
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

        $this->assertInternalType('array', $struct);
    }

    /**
     * Non-YAML files normally parse again to some array
     */
    public function testNonYamlFile()
    {
        $array = $this->createParser()->parseFile(__FILE__);

        $this->assertNull($array);
    }

    public function testYamlNull()
    {
        $array = $this->createParser()->parseBuffer('first: ~');

        $this->assertArrayHasKey('first', $array);
        $this->assertNull($array['first']);
    }

    /**
     * @return LibYaml
     */
    private function createParser()
    {
        return new LibYaml();
    }
}
