<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Info;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\Pipeline\Step;

/**
 * info about a step (for show output)
 */
final class StepInfo
{
    const NO_NAME = 'no-name';
    const CHAR_MANUAL = 'M';

    /**
     * @var Step
     */
    private $step;
    private $index;

    /**
     * @param Step $step
     * @param int $index (zero-based)
     */
    public function __construct(Step $step, $index)
    {
        $this->step = $step;
        $this->index = (int)$index;
    }

    /**
     * @param string $string
     * @param string $separator [optional]
     *
     * @return string
     */
    public function annotate($string, $separator = ' *')
    {
        $buffer = (string)$string;
        $annotations = $this->getAnnotations();
        if ($annotations) {
            $buffer .= $separator . implode('', $annotations);
        }

        return $buffer;
    }

    /**
     * @return array
     */
    public function getAnnotations()
    {
        $annotations = array();

        $this->step->isManual() && $annotations[] = self::CHAR_MANUAL;

        return $annotations;
    }

    /**
     * @return \Ktomk\Pipelines\File\Image
     */
    public function getImage()
    {
        return $this->step->getImage();
    }

    /**
     * @return string
     */
    public function getImageName()
    {
        return $this->step->getImage()->getName();
    }

    /**
     * @return string
     */
    public function getName()
    {
        $name = $this->step->getName();

        return null === $name ? self::NO_NAME : sprintf('"%s"', $name);
    }

    public function getStep()
    {
        return $this->step;
    }

    /**
     * @return int
     */
    public function getStepNumber()
    {
        return $this->index + 1;
    }

    /**
     * @return bool
     */
    public function hasDefaultImage()
    {
        return File::DEFAULT_IMAGE === $this->getImageName();
    }
}
