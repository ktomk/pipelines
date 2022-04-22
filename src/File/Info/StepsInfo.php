<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Info;

use Ktomk\Pipelines\File\ParseException;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\File\Pipeline\Steps;

/**
 * info about steps (for show output)
 *
 * info about steps include {@see StepInfo}.
 */
final class StepsInfo implements \Countable
{
    /**
     * @var ?Steps
     */
    private $steps;

    public static function fromPipeline(Pipeline $pipeline = null)
    {
        return new self($pipeline ? $pipeline->getSteps() : null);
    }

    /**
     * @param ?Steps $steps
     */
    public function __construct(Steps $steps = null)
    {
        $this->steps = $steps;
    }

    /**
     * @return array|string[] image names (unique), w/o the default image name
     */
    public function getImageNames()
    {
        $images = array();
        foreach (new StepsStepInfoIterator($this->steps) as $info) {
            if (false === $info->hasDefaultImage()) {
                $images[] = $info->getImageName();
            }
        }

        return array_unique($images);
    }

    public function getImagesAsString($separator = ', ')
    {
        $images = $this->getImageNames();

        return $images ? implode($separator, $images) : '';
    }

    public function getSummary(&$errorFree)
    {
        $errorFree = true;

        try {
            list($names, $annotations) = $this->getNamesAndAnnotations();
        } catch (ParseException $parseException) {
            $errorFree = false;

            return sprintf('%d ERROR %s', $this->count(), $parseException->getParseMessage());
        }

        return sprintf(
            '%d%s',
            $this->count(),
            $names ? ' (' . implode('; ', $this->annotate($names, ' *', $annotations)) . ')' : ''
        );
    }

    public function count()
    {
        return $this->steps ? count($this->steps) : 0;
    }

    /**
     * @return array [string[] images, string[] names, array<string>[] annotations]
     */
    private function getNamesAndAnnotations()
    {
        $names = array();
        $annotations = array();

        foreach (new StepsStepInfoIterator($this->steps) as $info) {
            $names[] = $info->getName();
            $annotations[] = $info->getAnnotations();
        }

        return array($names, $annotations);
    }

    /**
     * @param array<string> $names
     * @param string $annotator
     * @param array<string> $annotations
     *
     * @return array|string[]
     */
    private function annotate(array $names, $annotator, array $annotations)
    {
        return array_map(function ($name, $annotations) use ($annotator) {
            if ($annotations) {
                $name .= $annotator . implode('', $annotations);
            }

            return $name;
        }, $names, $annotations);
    }
}
