<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Exception;
use Ktomk\Pipelines\File;
use Ktomk\Pipelines\Step;

/**
 * Class FileShower
 *
 * Shows information about a file
 *
 * @package Ktomk\Pipelines\Utility
 */
class FileShower
{
    /**
     * @var callable
     */
    private $output;

    /**
     * @var File
     */
    private $file;

    /**
     * FileInfo constructor.
     * @param callable $output
     * @param File $file
     */
    public function __construct($output, File $file)
    {
        $this->output = $output;
        $this->file = $file;
    }

    /**
     * @throws \InvalidArgumentException
     * @return int
     */
    public function showImages()
    {
        $file = $this->file;

        /**
         * step iterator
         *
         * @param File $file
         * @return array|Step[]
         */
        $iterator = function (File $file) {
            $return = array();
            foreach ($file->getPipelines() as $id => $pipeline) {
                foreach ($pipeline->getSteps() as $index => $step) {
                    $return["${id}:/step/${index}"] = $step;
                }
            }

            return $return;
        };

        $images = array();
        foreach ($iterator($file) as $step) {
            $image = $step->getImage();
            $images[(string)$image] = $image;
        }

        foreach ($images as $image) {
            $this->info($image);
        }

        return 0;
    }

    public function showPipelineIds()
    {
        $file = $this->file;

        foreach ($file->getPipelineIds() as $id) {
            $this->info($id);
        }

        return 0;
    }

    /**
     * @return int 0 if there were no errors, 1 if there were errors
     */
    public function showPipelines()
    {
        $pipelines = $this->file;

        $errors = 0;
        $table = array(array('PIPELINE ID', 'IMAGES', 'STEPS'));
        foreach ($pipelines->getPipelineIds() as $id) {
            try {
                $pipeline = $pipelines->getById($id);
                $steps = $pipeline->getSteps();
            } catch (Exception $e) {
                $errors++;
                $table[] = array($id, 'ERROR', $e->getMessage());

                continue;
            }

            list($images, $names) = $this->getImagesAndNames($steps);

            $images = $images ? implode(', ', $images) : '';
            $steps = sprintf('%d%s', count($steps), $names ? ' ("' . implode('""; "', $names) . '")' : '');
            $table[] = array($id, $images, $steps);
        }

        $this->textTable($table);

        return $errors ? 1 : 0;
    }

    private function textTable(array $table)
    {
        $sizes = $this->textTableGetSizes($table);

        foreach ($table as $row) {
            $line = $this->textTableGetRow($row, $sizes);
            $this->info($line);
        }
    }

    private function info($message)
    {
        call_user_func($this->output, $message);
    }

    /**
     * @param array|Step[] $steps
     * @return array
     */
    private function getImagesAndNames(array $steps)
    {
        $images = array();
        $names = array();

        foreach ($steps as $step) {
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

    /**
     * get max sizes for each column in array table
     *
     * @param array $table
     * @return array|int[] sizes
     */
    private function textTableGetSizes(array $table)
    {
        $sizes = array();
        foreach ($table[0] as $index => $cell) {
            $sizes[$index] = 0;
        }

        foreach ($table as $row) {
            foreach ($row as $index => $column) {
                $sizes[$index] = max($sizes[$index], strlen($column));
            }
        }

        return $sizes;
    }

    /**
     * @param array|string[] $row
     * @param array|int[] $sizes
     * @return string
     */
    private function textTableGetRow(array $row, array $sizes)
    {
        $line = '';
        foreach ($row as $index => $column) {
            $len = strlen($column);
            $index && $line .= "    ";
            $line .= $column;
            $line .= str_repeat(' ', $sizes[$index] - $len);
        }

        return $line;
    }
}
