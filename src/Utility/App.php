<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\ParseException;
use Ktomk\Pipelines\File\Pipeline;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibFsPath;
use Ktomk\Pipelines\LibFsStream;
use Ktomk\Pipelines\Project;
use Ktomk\Pipelines\Runner\Directories;
use Ktomk\Pipelines\Runner\Flags;
use Ktomk\Pipelines\Runner\Reference;
use Ktomk\Pipelines\Runner\Runner;
use Ktomk\Pipelines\Runner\RunOpts;

class App implements Runnable
{
    const BBPL_BASENAME = 'bitbucket-pipelines.yml';

    const UTILITY_NAME = 'pipelines';

    const VERSION = '@.@.@';

    /**
     * @var Args
     */
    private $arguments;

    /**
     * @var Streams
     */
    private $streams;

    /**
     * @var bool
     */
    private $verbose = true;

    /**
     * @var Help
     */
    private $help;

    /**
     * @return App
     */
    public static function create()
    {
        $streams = Streams::create();

        return new self($streams);
    }

    public function __construct(Streams $streams)
    {
        $this->streams = $streams;
        $this->help = new Help($streams);
    }

    /**
     * @param array $arguments including the utility name in the first argument
     *
     * @throws InvalidArgumentException
     *
     * @return int 0-255
     */
    public function main(array $arguments)
    {
        $args = Args::create($arguments);

        $this->verbose = $args->hasOption(array('v', 'verbose'));
        $this->arguments = $args;

        $handler = new ExceptionHandler(
            $this->streams,
            $this->help,
            $args->hasOption('debug')
        );

        return $handler->handle($this);
    }

    /**
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     * @throws ParseException
     * @throws ArgsException
     * @throws StatusException
     * @throws \UnexpectedValueException
     *
     * @return int
     */
    public function run()
    {
        $args = $this->arguments;

        $this->help->run($args);

        $exec = $this->parseExec();

        $keep = KeepOptions::bind($args)->run();

        $basename = $this->parseBasename();

        $workingDir = $this->parseWorkingDir();

        $path = $this->parsePath($basename, $workingDir);

        $project = new Project($workingDir);

        // TODO: obtain project dir information etc. from VCS
        // $vcs = new Vcs();

        $runOpts = RunnerOptions::bind($args, $this->streams)->run();
        $project->setPrefix($runOpts->getPrefix());

        DockerOptions::bind($args, $exec, $project->getPrefix(), $this->streams)->run();

        $noRun = $args->hasOption('no-run');

        $deployMode = $this->parseDeployMode();

        $pipelines = File::createFromFile($path);

        ValidationOptions::bind($args, $this->streams, $pipelines)->run();

        $fileOptions = FileOptions::bind($args, $this->streams, $pipelines)->run();

        $reference = $this->parseReference();

        $env = EnvParser::create($this->arguments)
            ->parse(Lib::env($_SERVER), $reference, $project->getPath());

        $directories = new Directories(Lib::env($_SERVER), $project);

        ServiceOptions::bind(
            $args,
            $this->streams,
            $pipelines,
            $exec,
            $env,
            $runOpts,
            $directories
        )->run();

        $pipelineId = $args->getOptionArgument(
            'pipeline',
            $pipelines->searchIdByReference($reference) ?: 'default'
        );

        $streams = $this->parseStreams();

        $this->parseRemainingOptions();

        $pipeline = $this->getRunPipeline($pipelines, $pipelineId, $fileOptions, $runOpts);

        $flags = $this->getRunFlags($keep, $deployMode);

        $runner = Runner::createEx($runOpts, $directories, $exec, $flags, $env, $streams);

        if ($noRun) {
            $this->verbose('info: not running the pipeline per --no-run option');
            $status = 0;
        } else {
            $status = $runner->run($pipeline);
        }

        return $status;
    }

    /**
     * @throws StatusException
     *
     * @return string
     */
    private function getWorkingDirectory()
    {
        $workingDir = \getcwd();
        if (false === $workingDir) {
            // @codeCoverageIgnoreStart
            throw new StatusException('fatal: obtain working directory', 1);
            // @codeCoverageIgnoreEnd
        }

        return $workingDir;
    }

    /**
     * @throws InvalidArgumentException
     * @throws StatusException
     * @throws ArgsException
     *
     * @return string basename for bitbucket-pipelines.yml
     */
    private function parseBasename()
    {
        $args = $this->arguments;

        $basename = $args->getStringOptionArgument('basename', self::BBPL_BASENAME);
        if (!LibFsPath::isBasename($basename)) {
            throw new StatusException(sprintf("not a basename: '%s'", $basename), 1);
        }

        return $basename;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ArgsException
     * @throws StatusException
     *
     * @return string deploy mode ('copy', 'mount')
     */
    private function parseDeployMode()
    {
        $args = $this->arguments;

        $deployMode = $args->getStringOptionArgument('deploy', 'copy');

        if (!in_array($deployMode, array('mount', 'copy'), true)) {
            throw new StatusException(
                sprintf("unknown deploy mode '%s'\n", $deployMode),
                1
            );
        }

        return $deployMode;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return Exec
     */
    private function parseExec()
    {
        $args = $this->arguments;

        $debugPrinter = null;
        if ($this->verbose) {
            $debugPrinter = $this->streams;
        }
        $exec = new Exec($debugPrinter);

        if ($args->hasOption('dry-run')) {
            $exec->setActive(false);
        }

        return $exec;
    }

    /**
     * @param string $basename
     * @param string $workingDir
     *
     * @throws InvalidArgumentException
     * @throws StatusException
     * @throws ArgsException
     *
     * @return string file
     */
    private function parseFile($basename, &$workingDir)
    {
        $args = $this->arguments;

        /** @var null|string $file as bitbucket-pipelines.yml to process */
        $file = $args->getOptionArgument('file', null);
        if (null === $file && null !== $file = LibFs::fileLookUp($basename, $workingDir)) {
            $buffer = dirname($file);
            if ($buffer !== $workingDir) {
                $this->changeWorkingDir($buffer);
                $workingDir = $this->getWorkingDirectory();
            }
        }

        if (empty($file)) {
            throw new StatusException('file can not be empty', 1);
        }

        return $file;
    }

    /**
     * @param string $basename
     * @param string $workingDir
     *
     * @throws InvalidArgumentException
     * @throws ArgsException
     * @throws StatusException
     *
     * @return string path
     */
    private function parsePath($basename, &$workingDir)
    {
        $buffer = (string)$workingDir;

        $file = $this->parseFile($basename, $workingDir);

        $this->verbose(sprintf(
            'info: project directory is %s',
            $workingDir === $buffer
                ? sprintf("'%s'", $workingDir)
                : sprintf("'%s' (OLDPWD: '%s')", $workingDir, $buffer)
        ));

        if ($file !== $basename && self::BBPL_BASENAME !== $basename) {
            $this->verbose('info: --file overrides non-default --basename');
        }

        // full path to bitbucket-pipelines.yml to process
        $path = LibFsPath::isAbsolute($file)
            ? $file
            : $workingDir . '/' . $file;

        // support stdin and process substitution for pipelines file
        if ($file !== LibFsStream::mapFile($file)) {
            $this->verbose(sprintf('info: reading pipelines from %s', '-' === $file ? 'stdin' : $file));

            return $file;
        }

        if (!LibFs::isReadableFile($file)) {
            throw new StatusException(sprintf('not a readable file: %s', $file), 1);
        }

        $this->verbose(sprintf("info: pipelines file is '%s'", $path));

        return $path;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ArgsException
     *
     * @return Reference
     */
    private function parseReference()
    {
        $trigger = $this->arguments->getOptionArgument('trigger');

        return Reference::create($trigger);
    }

    /**
     * give error about unknown option, show usage and exit status of 1
     *
     * @throws ArgsException
     *
     * @return void
     */
    private function parseRemainingOptions()
    {
        $option = $this->arguments->getFirstRemainingOption();

        if ($option) {
            throw new ArgsException(
                sprintf('unknown option: %s', $option)
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return Streams
     */
    private function parseStreams()
    {
        $streams = $this->streams;

        // --verbatim show only errors for own runner actions, show everything from pipeline verbatim
        if ($this->arguments->hasOption('verbatim')) {
            $streams = new Streams();
            $streams->copyHandle($this->streams, 2);
        }

        return $streams;
    }

    /**
     * @throws InvalidArgumentException
     * @throws StatusException
     * @throws ArgsException
     *
     * @return string current working directory
     */
    private function parseWorkingDir()
    {
        $args = $this->arguments;

        $buffer = $args->getStringOptionArgument('working-dir', '');

        if ('' !== $buffer) {
            $this->changeWorkingDir($buffer);
        }

        return $this->getWorkingDirectory();
    }

    /**
     * @param string $directory
     *
     * @throws StatusException
     *
     * @return void
     */
    private function changeWorkingDir($directory)
    {
        $message = sprintf('changing working directory to %s', $directory);
        $this->verbose(sprintf('info: %s', $message));

        $result = chdir($directory);
        if (false === $result) {
            throw new StatusException(sprintf('fatal: %s', $message), 2);
        }
    }

    /**
     * Obtain pipeline to run from file while handling error output
     *
     * @param File $pipelines
     * @param string $pipelineId
     * @param FileOptions $fileOptions
     * @param RunOpts $runOpts
     *
     * @throws ParseException
     * @throws StatusException
     *
     * @return Pipeline on success
     */
    private function getRunPipeline(File $pipelines, $pipelineId, FileOptions $fileOptions, RunOpts $runOpts)
    {
        $this->verbose(sprintf("info: running pipeline '%s'", $pipelineId));

        $pipeline = null;

        try {
            $pipeline = $pipelines->getById($pipelineId);
        } catch (ParseException $e) {
            $this->error(sprintf("pipelines: error: pipeline id '%s'", $pipelineId));

            throw $e;
        } catch (InvalidArgumentException $e) {
            $this->error(sprintf("pipelines: pipeline '%s' unavailable", $pipelineId));
            $this->info('Pipelines are:');
            $fileOptions->showPipelines($pipelines);

            throw new StatusException('', 1);
        }

        if (!$pipeline) {
            throw new StatusException('no pipeline to run!', 1);
        }

        $pipeline->setStepsExpression($runOpts->getSteps());

        return $pipeline;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function error($message)
    {
        $this->streams->err(
            sprintf("%s\n", $message)
        );
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function info($message)
    {
        $this->streams->out(
            sprintf("%s\n", $message)
        );
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function verbose($message)
    {
        if ($this->verbose) {
            $this->info($message);
        }
    }

    /**
     * Map diverse parameters to run flags
     *
     * @param KeepOptions $keep
     * @param string $deployMode
     *
     * @return Flags
     */
    private function getRunFlags(KeepOptions $keep, $deployMode)
    {
        $flagsValue = Flags::FLAGS;
        if ($keep->errorKeep) {
            $flagsValue |= Flags::FLAG_KEEP_ON_ERROR;
        } elseif ($keep->keep) {
            $flagsValue &= ~(Flags::FLAG_DOCKER_KILL | Flags::FLAG_DOCKER_REMOVE);
        }

        if ('copy' === $deployMode) {
            $flagsValue |= Flags::FLAG_DEPLOY_COPY;
        }

        return new Flags($flagsValue);
    }
}
