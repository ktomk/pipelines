<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\ReferenceTypes;
use Ktomk\Pipelines\Runner\RunOpts;
use Ktomk\Pipelines\Runner\StepScriptWriter;
use RuntimeException;

/**
 * script specific options of the pipelines utility
 *
 * ** POC **
 *
 * --step-script[=(<id> | <step>[:<id>])]
 *
 * dump step-script to stdout. by default the first <step> script of the default pipeline <id>
 *
 * @package Ktomk\Pipelines\Utility
 */
class StepScriptOption
{
    /**
     * @var Streams
     */
    private $streams;

    /**
     * @var Args
     */
    private $args;

    /**
     * @var File
     */
    private $file;
    /**
     * @var RunOpts
     */
    private $runOpts;

    /**
     * bind options
     *
     * @param Args $args
     * @param Streams $streams
     * @param File $file
     *
     * @return StepScriptOption
     */
    public static function bind(Args $args, Streams $streams, File $file, RunOpts $runOpts)
    {
        return new self($args, $streams, $file, $runOpts);
    }

    /**
     * DockerOptions constructor.
     *
     * @param Args $args
     * @param Streams $streams
     * @param File $file
     * @param RunOpts $runOpts
     */
    public function __construct(Args $args, Streams $streams, File $file, RunOpts $runOpts)
    {
        $this->args = $args;
        $this->streams = $streams;
        $this->file = $file;
        $this->runOpts = $runOpts;
    }

    /**
     * Process --step-script option
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws StatusException
     *
     * @return void
     */
    public function run()
    {
        $ref = $this->args->getOptionOptionalArgument('step-script', ReferenceTypes::SEG_DEFAULT);
        if (null !== $ref) {
            $step = 1;
            $id = $ref;
            $n = sscanf($ref, '%d:%s', $step, $id);
            $id = (1 === $n || '' === $id) ? ReferenceTypes::SEG_DEFAULT : $id;
            if (!ReferenceTypes::isValidId($id)) {
                StatusException::fatal(sprintf('invalid pipeline "%s"', $id));
            }
            $pipeline = $this->file->getById($id);
            if (null === $pipeline) {
                StatusException::fatal(sprintf('not a pipeline "%s"', $id));
            }
            $steps = $pipeline->getSteps();
            if (0 >= $step || $step > $steps->count()) {
                StatusException::fatal(sprintf('pipeline "%s" has no step #%d', $id, $step));
            }
            $script = StepScriptWriter::writeStepScript(
                $steps[$step - 1]->getScript(),
                $this->runOpts->getBoolOption('script.exit-early')
            );
            $this->streams->out($script);
            StatusException::ok();
        }
    }
}
