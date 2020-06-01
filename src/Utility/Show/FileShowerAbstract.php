<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility\Show;

use InvalidArgumentException;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\ParseException;
use Ktomk\Pipelines\File\Pipelines;

/**
 * Class FileShowerAbstract
 *
 * Show details about the pipelines file on text output
 *
 * @package Ktomk\Pipelines\Utility\Show
 */
class FileShowerAbstract
{
    /**
     * @var callable
     */
    protected $output;

    /**
     * @var File
     */
    protected $file;

    /**
     * FileInfo constructor.
     *
     * @param callable $output
     * @param File $file
     */
    public function __construct($output, File $file)
    {
        $this->output = $output;
        $this->file = $file;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function info($message)
    {
        call_user_func($this->output, $message);
    }

    /* table show routines */

    /**
     * @param Pipelines $pipelines
     * @param array $table
     * @param int $errors
     *
     * @return array
     */
    protected function tablePipelineIdsPipelines(Pipelines $pipelines, array &$table, &$errors)
    {
        $return = array();

        foreach ($pipelines->getPipelineIds() as $id) {
            list($pipeline, $message) = $this->getShowPipeline($pipelines, $id);
            if ($message) {
                $table[] = array($id, 'ERROR', $message);
                $errors++;

                continue;
            }
            $return[$id] = $pipeline;
        }

        return $return;
    }

    /* text table implementation */

    /**
     * @param array $table
     *
     * @return void
     */
    protected function textTable(array $table)
    {
        $sizes = $this->textTableGetSizes($table);

        foreach ($table as $row) {
            $line = $this->textTableGetRow($row, $sizes);
            $this->info($line);
        }
    }

    /**
     * @param Pipelines $pipelines
     * @param string $id
     *
     * @return array
     */
    private function getShowPipeline(Pipelines $pipelines, $id)
    {
        $pipeline = null;
        $message = null;

        try {
            $pipeline = $pipelines->getById($id);
        } catch (ParseException $exception) {
            $message = $exception->getParseMessage();
        } catch (InvalidArgumentException $exception) {
            $message = $exception->getMessage();
        }

        return array($pipeline, $message);
    }

    /**
     * get max sizes for each column in array table
     *
     * @param array $table
     *
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
     *
     * @return string
     */
    private function textTableGetRow(array $row, array $sizes)
    {
        $line = '';
        foreach ($row as $index => $column) {
            $len = strlen($column);
            $index && $line .= '    ';
            $line .= $column;
            $line .= str_repeat(' ', $sizes[$index] - $len);
        }

        return $line;
    }
}
