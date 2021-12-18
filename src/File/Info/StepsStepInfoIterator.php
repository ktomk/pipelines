<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Info;

use Ktomk\Pipelines\File\Pipeline\Steps;

final class StepsStepInfoIterator extends \IteratorIterator
{
    /**
     * @var ?Steps
     */
    private $steps;

    /**
     * @param ?Steps $steps
     */
    public function __construct(Steps $steps = null)
    {
        $this->steps = $steps;
        parent::__construct(Steps::fullIter($steps));
    }

    #[\ReturnTypeWillChange]
    /**
     * @return StepInfo
     */
    public function current()
    {
        return new StepInfo(parent::current(), (int)$this->key());
    }
}
