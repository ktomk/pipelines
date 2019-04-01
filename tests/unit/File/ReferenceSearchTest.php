<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\File;
use Ktomk\Pipelines\Pipeline;
use Ktomk\Pipelines\Runner\Reference;
use PHPUnit\Framework\TestCase;

/**
 * @covers  \Ktomk\Pipelines\File::searchReference()
 * @covers  \Ktomk\Pipelines\File::searchTypeReference()
 */
class ReferenceSearchTest extends TestCase
{
    /**
     * @var File
     */
    private $file;

    protected function setUp()
    {
        parent::setUp();
        $this->file = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml');
    }

    public function searchReference($ref = null)
    {
        return $this->file->searchReference(
            Reference::create($ref)
        );
    }

    public function testSearching()
    {
        $actual = $this->searchReference('branch:feature/unicorns');
        $this->assertNotNull($actual);
    }

    public function testSearchDirect()
    {
        $actual = $this->searchReference('branch:feature/unicorns');
        $this->assertSame('feature/*', $this->getFirstStepName($actual));

        $actual = $this->searchReference('branch:feature/bb-123-fix-links');
        $this->assertSame('feature/bb-123-fix-links', $this->getFirstStepName($actual));
    }

    public function testSearchFirstPatternNoHit()
    {
        $actual = $this->searchReference('tag:blue-moon-unicorn-release');
        $this->assertDefault($actual);
    }

    public function testSearchNothing()
    {
        $actual = $this->searchReference();
        $this->assertDefault($actual);
    }

    public function testSearchByBracePatternInBranch()
    {
        $actual = $this->searchReference('branch:bar');
        $this->assertSame('foo and bar branches', $this->getFirstStepName($actual));
    }

    private function getFirstStepName(Pipeline $pipeline)
    {
        $steps = $pipeline->getSteps();

        return $steps[0]->getName();
    }

    private function assertDefault(Pipeline $pipeline)
    {
        $default = $this->file->getDefault();
        $this->assertSame($default, $pipeline, 'is default pipeline');
    }
}
