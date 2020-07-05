<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility\Show;

use InvalidArgumentException;
use Ktomk\Pipelines\File\Definitions\Service;
use Ktomk\Pipelines\File\Definitions\Services as Services;
use Ktomk\Pipelines\File\File;
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
            $this->info($image);
        }

        return 0;
    }

    /**
     * @return int
     */
    public function showPipelineIds()
    {
        $pipelines = $this->file->getPipelines();

        foreach ($pipelines->getPipelineIds() as $id) {
            $this->info($id);
        }

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

        $errors = 0;
        $table = array(array('PIPELINE ID', 'STEP', 'IMAGE', 'NAME'));
        foreach ($this->tablePipelineIdsPipelines($pipelines, $table, $errors) as $id => $pipeline) {
            $steps = (null === $pipeline) ? array() : $pipeline->getSteps();
            list($table, $errors) = $this->tableFileSteps($steps, $id, $table, $errors);
        }

        $this->textTable($table);

        return $errors ? 1 : 0;
    }

    /**
     * shows summary of the file, first pipelines then pipline services
     *
     * @return int 0 if there were no errors, 1 if there were errors
     */
    public function showPipelines()
    {
        $pipelines = $this->file->getPipelines();

        $errors = 0;
        $table = array(array('PIPELINE ID', 'IMAGES', 'STEPS'));
        foreach ($this->tablePipelineIdsPipelines($pipelines, $table, $errors) as $id => $pipeline) {
            $steps = (null === $pipeline) ? null : $pipeline->getSteps();
            list($images, $names) = $this->getImagesAndNames($steps);

            $images = $images ? implode(', ', $images) : '';
            $steps = sprintf('%d%s', count($steps), $names ? ' ("' . implode('"; "', $names) . '")' : '');
            $table[] = array($id, $images, $steps);
        }

        $this->textTable($table);

        return $errors ? 1 : 0;
    }

    /**
     * @return int
     */
    public function showServices()
    {
        $file = $this->file;
        $pipelines = $file->getPipelines();

        $errors = 0;
        $table = array(array('PIPELINE ID', 'STEP', 'SERVICE', 'IMAGE'));

        try {
            $serviceDefinitions = $file->getDefinitions()->getServices();
        } catch (ParseException $e) {
            $table[] = array('', '', 'ERROR', $e->getParseMessage());
            $this->textTable($table);

            return 1;
        }

        foreach ($this->tablePipelineIdsPipelines($pipelines, $table, $errors) as $id => $pipeline) {
            list($table, $errors) =
                $this->tableStepsServices($pipeline->getSteps(), $serviceDefinitions, $id, $table, $errors);
        }

        $this->textTable($table);

        return $errors ? 1 : 0;
    }

    /**
     * @param Step[]|Steps $steps
     * @param string $id
     * @param array $table
     * @param int $errors
     *
     * @return array
     */
    private function tableFileSteps($steps, $id, array $table, $errors)
    {
        foreach ($steps as $index => $step) {
            $name = $step->getName();
            null !== $name && $name = sprintf('"%s"', $name);
            null === $name && $name = 'no-name';

            $table[] = array($id, $index + 1, $step->getImage(), $name);
            list($table, $errors) = $this->tableFileStepsServices(
                $step->getServices()->getServiceNames(),
                $step,
                $id,
                $index + 1,
                $table,
                $errors
            );
        }

        return array($table, $errors);
    }

    /**
     * @param array|string[] $serviceNames
     * @param Step $step
     * @param string $id
     * @param int $stepNumber 1 based
     * @param array $table
     * @param int $errors
     *
     * @return array
     */
    private function tableFileStepsServices($serviceNames, Step $step, $id, $stepNumber, array $table, $errors)
    {
        foreach ($serviceNames as $serviceName) {
            /** @var Service $service */
            ($service = $step->getFile()->getDefinitions()->getServices()->getByName($serviceName)) || $errors++;
            $table[] = array($id, $stepNumber, $service ? $service->getImage() : 'ERROR', 'service:' . $serviceName);
        }

        return array($table, $errors);
    }

    /**
     * @param Steps $steps
     * @param Services $serviceDefinitions
     * @param string $id
     * @param array $table
     * @param int $errors
     *
     * @return array
     */
    private function tableStepsServices(Steps $steps, Services $serviceDefinitions, $id, array $table, $errors)
    {
        foreach ($steps as $step) {
            $serviceNames = $step->getServices()->getServiceNames();
            if (empty($serviceNames)) {
                continue;
            }

            $stepNo = $step->getIndex() + 1;

            foreach ($serviceNames as $name) {
                if (!$service = $serviceDefinitions->getByName($name)) {
                    $table[] = array($id, $stepNo, 'ERROR', sprintf('Undefined service: "%s"', $name));
                    $errors++;
                } else {
                    $table[] = array($id, $stepNo, $name, $service->getImage());
                }
            }
        }

        return array($table, $errors);
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

    /**
     * @param Steps $steps
     *
     * @return array
     */
    private function getImagesAndNames(Steps $steps = null)
    {
        $images = array();
        $names = array();

        foreach (Steps::fullIter($steps) as $step) {
            $image = $step->getImage()->getName();
            if (File::DEFAULT_IMAGE !== $image) {
                $images[] = $image;
            }
            $name = $step->getName();
            (null !== $name) && $names[] = $name;
        }

        $images = array_unique($images);

        return array($images, $names);
    }
}
