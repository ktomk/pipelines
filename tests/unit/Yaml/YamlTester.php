<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use PHPUnit\Framework\TestCase;

class YamlTester
{
    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @var ParserInterface
     */
    private $parser;

    public function __construct(TestCase $testCase, ParserInterface $parser)
    {
        $this->testCase = $testCase;
        $this->parser = $parser;
    }

    public function assertParser()
    {
        $parser = $this->parser;

        $class = get_class($parser);

        $this->testCase->assertInternalType('bool', $class::isAvailable(), 'static availability returns bool');

        $this->testCase->assertNull($parser->parseFile('xxx'), 'non existing file returns NULL');

        $this->testCase->assertNull($parser->parseFile('data://text/plain,'), 'empty YAML stream returns NULL');

        $this->testCase->assertNull($parser->parseFile(__FILE__), 'non YAML file returns NULL');
    }
}
