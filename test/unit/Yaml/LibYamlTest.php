<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\TestCase;

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

    /**
     * @covers \Ktomk\Pipelines\Yaml\Yaml::fileDelegate
     *
     * @return void
     */
    public function testFileParsing()
    {
        $path = __DIR__ . '/../../../bitbucket-pipelines.yml';

        $struct = $this->createParser()->tryParseFile($path);

        self::assertIsArray($struct);

        $struct = $this->createParser()->parseFile($path);

        self::assertIsArray($struct);
    }

    /**
     * Non-YAML files normally parse again to some array
     */
    public function testNonYamlFile()
    {
        $array = $this->createParser()->tryParseFile(__FILE__);

        self::assertNull($array);

        $this->expectException(__NAMESPACE__ . '\ParseException');
        $this->expectExceptionMessage('yaml_parse(): scanning error encountered during parsing: mapping values are not allowed in this context (line 63, column 52)');
        $this->createParser()->parseFile(__FILE__);
    }

    public function testYamlNull()
    {
        $array = $this->createParser()->tryParseBuffer('first: ~');

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
