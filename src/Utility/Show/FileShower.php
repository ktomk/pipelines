<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility\Show;

use InvalidArgumentException;
use Ktomk\Pipelines\File\Definitions\Service;
use Ktomk\Pipelines\File\Definitions\Services;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\Info\StepsInfo;
use Ktomk\Pipelines\File\Info\StepsStepInfoIterator;
use Ktomk\Pipelines\File\ParseException;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\File\Pipeline\Steps;

/**
 * Class FileShower
 *
 * Shows information about a file
 *
 * @package Ktomk\Pipelines\Utility
 */
class FileShower extends FileShowerAbstract
{
    /**
     * @throws InvalidArgumentException
     *
     * @return int
     */
    public function showImages()
    {
        $images = array();

        foreach ($this->getAllStepsWithServices($this->file) as $step) {
            $image = $step->getImage();
            $images[(string)$image] = $image;
        }

        foreach ($images as $image) {
            $this->info((string)$image);
        }

        return 0;
    }

    /**
     * @return int
     */
    public function showPipelineIds()
    {
        array_map(
            array($this, 'info'),
            $this->file->getPipelines()->getPipelineIds()
        );

        return 0;
    }

    /**
     * shows pipeline and steps
     *
     * @return int 0 if there were no errors, 1 if there were errors
     */
    public function showFile()
    {
        $pipelines = $this->file->getPipelines();

        $table = new FileTable(array('PIPELINE ID', 'STEP', 'IMAGE', 'NAME'));

        foreach ($this->tablePipelineIdsPipelines($pipelines, $table) as $id => $pipeline) {
            $steps = (null === $pipeline) ? array() : $pipeline->getSteps();
            $this->tableFileSteps($steps, $id, $table);
        }

        return $this->outputTableAndReturn($table);
    }

    /**
     * shows summary of the file, first pipelines then pipeline services
     *
     * @return int 0 if there were no errors, 1 if there were errors
     */
    public function showPipelines()
    {
        $pipelines = $this->file->getPipelines();

        $table = new FileTable(array('PIPELINE ID', 'IMAGES', 'STEPS'));

        foreach ($this->tablePipelineIdsPipelines($pipelines, $table) as $id => $pipeline) {
            $info = StepsInfo::fromPipeline($pipeline);
            $summary = $info->getSummary($hasError);
            $table->addFlaggedRow($hasError, array($id, $info->getImagesAsString(), $summary));
        }

        return $this->outputTableAndReturn($table);
    }

    /**
     * @return int
     */
    public function showServices()
    {
        $file = $this->file;

        $table = new FileTable(array('PIPELINE ID', 'STEP', 'SERVICE', 'IMAGE'));

        try {
            $serviceDefinitions = $file->getDefinitions()->getServices();
            foreach ($this->tablePipelineIdsPipelines($file->getPipelines(), $table) as $id => $pipeline) {
                $this->tableStepsServices($pipeline->getSteps(), $serviceDefinitions, $id, $table);
            }
        } catch (ParseException $e) {
            $table->addErrorRow(array('', '', 'ERROR', $e->getParseMessage()));
        }

        return $this->outputTableAndReturn($table);
    }

    /**
     * @param Step[]|Steps $steps
     * @param string $id
     * @param FileTable $table
     *
     * @return void
     */
    private function tableFileSteps($steps, $id, FileTable $table)
    {
        foreach (new StepsStepInfoIterator($steps) as $info) {
            $number = $info->getStepNumber();
            $annotate = $info->annotate($number, null, $errorFree);
            $table->addFlaggedRow($errorFree, array($id, $annotate, $info->getImage(), $info->getName()));
            $this->tableFileStepsCaches($step = $info->getStep(), $id, $number, $table);
            $this->tableFileStepsServices($step, $id, $number, $table);
        }
    }

    /**
     * @param Step $step
     * @param string $id
     * @param int $stepNumber 1 based
     * @param FileTable $table
     *
     * @return void
     */
    private function tableFileStepsCaches(Step $step, $id, $stepNumber, FileTable $table)
    {
        $caches = $step->getCaches();
        $cacheDefinitions = $step->getFile()->getDefinitions()->getCaches();

        foreach ($caches->getNames() as $cacheName) {
            $cacheLabel = 'cache: ' . $cacheName;
            $definition = $cacheDefinitions->getByName($cacheName);
            $cacheDescription = true === $definition ? '*internal*' : $definition;
            $table->addRow(array($id, $stepNumber, $cacheLabel, $cacheDescription));
        }
    }

    /**
     * @param Step $step
     * @param string $id
     * @param int $stepNumber 1 based
     * @param FileTable $table
     *
     * @return void
     */
    private function tableFileStepsServices(Step $step, $id, $stepNumber, FileTable $table)
    {
        foreach ($step->getServices()->getServiceNames() as $serviceName) {
            /** @var Service $service */
            $service = $step->getFile()->getDefinitions()->getServices()->getByName($serviceName);
            $table->addFlaggedRow(
                $service,
                array($id, $stepNumber, $service ? $service->getImage() : 'ERROR', 'service:' . $serviceName)
            );
        }
    }

    /**
     * @param Steps $steps
     * @param Services $serviceDefinitions
     * @param string $id
     * @param FileTable $table
     *
     * @return void
     */
    private function tableStepsServices(Steps $steps, Services $serviceDefinitions, $id, FileTable $table)
    {
        foreach ($steps as $step) {
            $serviceNames = $step->getServices()->getServiceNames();
            if (empty($serviceNames)) {
                continue;
            }

            $stepNo = $step->getIndex() + 1;

            foreach ($serviceNames as $name) {
                if ($service = $serviceDefinitions->getByName($name)) {
                    $table->addRow(array($id, $stepNo, $name, $service->getImage()));
                } else {
                    $table->addErrorRow(array($id, $stepNo, 'ERROR', sprintf('Undefined service: "%s"', $name)));
                }
            }
        }
    }

    /**
     * @param File $file
     *
     * @return Step[]
     */
    private function getAllSteps(File $file)
    {
        $return = array();
        foreach ($file->getPipelines()->getPipelines() as $id => $pipeline) {
            foreach (Steps::fullIter($pipeline->getSteps()) as $index => $step) {
                $return["${id}:/step/${index}"] = $step;
            }
        }

        return $return;
    }

    /**
     * step iterator w/services
     *
     * @param File $file
     *
     * @return Service[]|Step[]
     */
    private function getAllStepsWithServices(File $file)
    {
        $return = array();

        foreach ($this->getAllSteps($file) as $key => $step) {
            $return[$key] = $step;
            foreach ($step->getServices()->getDefinitions() as $name => $service) {
                $return["${key}/service/${name}"] = $service;
            }
        }

        return $return;
    }
}
