<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\Runner\Reference;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\File\Pipelines::searchReference()
 * @covers \Ktomk\Pipelines\File\Pipelines::searchTypeReference()
 * @covers \Ktomk\Pipelines\File\PipelinesReferences
 */
class ReferenceSearchTest extends TestCase
{
    /**
     * @var Pipelines
     */
    private $pipelines;

    protected function doSetUp()
    {
        parent::doSetUp();
        $this->pipelines = File::createFromFile(__DIR__ . '/../../data/yml/bitbucket-pipelines.yml')->getPipelines();
    }

    public function searchReference($ref = null)
    {
        return $this->pipelines->searchReference(
            Reference::create($ref)
        );
    }

    public function testSearching()
    {
        $actual = $this->searchReference('branch:feature/unicorns');
        self::assertNotNull($actual);
    }

    public function testSearchDirect()
    {
        $actual = $this->searchReference('branch:feature/unicorns');
        self::assertSame('feature/*', $this->getFirstStepName($actual));

        $actual = $this->searchReference('branch:feature/bb-123-fix-links');
        self::assertSame('feature/bb-123-fix-links', $this->getFirstStepName($actual));
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
        self::assertSame('foo and bar branches', $this->getFirstStepName($actual));
    }

    private function getFirstStepName(Pipeline $pipeline)
    {
        $steps = $pipeline->getSteps();

        return $steps[0]->getName();
    }

    private function assertDefault(Pipeline $pipeline)
    {
        $default = $this->pipelines->getDefault();
        self::assertSame($default, $pipeline, 'is default pipeline');
    }
}
