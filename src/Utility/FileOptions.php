<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File;

class FileOptions
{
    /**
     * @var Args
     */
    private $args;

    /**
     * @var callable
     */
    private $output;

    /**
     * @var File
     */
    private $file;

    /**
     * FileOptions constructor.
     * @param Args $args
     * @param callable $output
     * @param File $file
     */
    public function __construct(Args $args, $output, File $file)
    {
        $this->args = $args;
        $this->output = $output;
        $this->file = $file;
    }

    /**
     * bind options
     *
     * @param Args $args
     * @param Streams $streams
     * @param File $file
     * @return FileOptions
     */
    public static function bind(Args $args, Streams $streams, File $file)
    {
        return new self($args, $streams, $file);
    }

    /**
     * run options
     *
     * @throws InvalidArgumentException
     * @throws StatusException
     * @return $this
     */
    public function run()
    {
        $args = $this->args;

        if ($args->hasOption('images')) {
            StatusException::status($this->shower()->showImages());
        }

        if ($args->hasOption('show')) {
            StatusException::status($this->shower()->showPipelines());
        }

        if ($args->hasOption('list')) {
            StatusException::status($this->shower()->showPipelineIds());
        }

        return $this;
    }

    /**
     * @param $pipelines
     * @return int
     */
    public function showPipelines(File $pipelines)
    {
        return $this->shower($pipelines)->showPipelines();
    }

    /**
     * @param null|File $file [optional]
     * @return FileShower
     */
    private function shower(File $file = null)
    {
        if (null === $file) {
            $file = $this->file;
        }

        return new FileShower($this->output, $file);
    }
}
