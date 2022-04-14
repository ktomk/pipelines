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

        $testCase = $this->testCase;

        $testCase::assertIsBool($parser::isAvailable(), 'static availability returns bool');

        $testCase::assertNull($parser->tryParseFile('xxx'), 'non existing file returns NULL');

        $testCase::assertNull($parser->tryParseFile('data://text/plain,'), 'empty YAML stream returns NULL');

        $testCase::assertNull($parser->tryParseFile(__FILE__), 'non YAML file returns NULL');
    }
}
