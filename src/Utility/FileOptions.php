<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\Utility\Show\FileShower;

class FileOptions implements Runnable
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
     * bind options
     *
     * @param Args $args
     * @param Streams $streams
     * @param File $file
     *
     * @return FileOptions
     */
    public static function bind(Args $args, Streams $streams, File $file)
    {
        return new self($args, $streams, $file);
    }

    /**
     * FileOptions constructor.
     *
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
     * run options
     *
     * @throws InvalidArgumentException
     * @throws StatusException
     *
     * @return $this
     */
    public function run()
    {
        $shower = $this->shower();

        $this->args->mapOption(
            array(
                'images' => 'showImages', /** @see FileShower::showImages() */
                'show' => 'showFile', /** @see FileShower::showFile() */
                'show-pipelines' => 'showPipelines', /** @see FileShower::showPipelines() */
                'show-services' => 'showServices', /** @see FileShower::showServices() */
                'list' => 'showPipelineIds', /** @see FileShower::showPipelineIds() */
            ),
            /**
             * @param string $option
             * @param string $method
             *
             * @throws StatusException
             */
            function ($option, $method) use ($shower) {
                throw new StatusException('', $shower->{$method}());
            }
        );

        return $this;
    }

    /**
     * @param File $pipelines
     *
     * @return int
     */
    public function showPipelines(File $pipelines)
    {
        return $this->shower($pipelines)->showPipelines();
    }

    /**
     * @param null|File $file [optional]
     *
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
