<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\Pipeline;
use Ktomk\Pipelines\Runner;
use Ktomk\Pipelines\Runner\Env;

class App implements Runnable
{
    const BBPL_BASENAME = 'bitbucket-pipelines.yml';

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

    public function __construct(Streams $streams)
    {
        $this->streams = $streams;
        $this->help = new Help($streams);
    }

    /**
     * @return App
     */
    public static function create()
    {
        $streams = Streams::create();

        return new self($streams);
    }

    /**
     * @param array $arguments including the utility name in the first argument
     * @throws InvalidArgumentException
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
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     * @throws \Ktomk\Pipelines\File\ParseException
     * @throws ArgsException
     * @throws StatusException
     * @return int
     */
    public function run()
    {
        $args = $this->arguments;

        $this->help->run($args);

        $prefix = $this->parsePrefix();

        $exec = $this->parseExec();

        DockerOptions::bind($args, $exec, $prefix, $this->streams)->run();

        $keep = KeepOptions::bind($args)->run();

        $basename = $this->parseBasename();

        $workingDir = $this->parseWorkingDir();

        $path = $this->parsePath($basename, $workingDir);

        // TODO: obtain project dir information etc. from VCS
        // $vcs = new Vcs();

        $noRun = $args->hasOption('no-run');

        $deployMode = $this->parseDeployMode();

        $pipelines = File::createFromFile($path);

        $fileOptions = FileOptions::bind($args, $this->streams, $pipelines)->run();

        $reference = $this->parseReference();

        $env = $this->parseEnv($_SERVER, $reference, $workingDir);

        $pipelineId = $pipelines->searchIdByReference($reference) ?: 'default';

        $pipelineId = $args->getOptionArgument('pipeline', $pipelineId);

        $streams = $this->parseStreams();

        $this->parseRemainingOptions();

        $pipeline = $this->getRunPipeline($pipelines, $pipelineId, $fileOptions);

        $flags = $this->getRunFlags($keep, $deployMode);

        $directories = new Runner\Directories($_SERVER, $workingDir);

        $runner = new Runner($prefix, $directories, $exec, $flags, $env, $streams);

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
     * @return string
     */
    private function getWorkingDirectory()
    {
        $workingDir = \getcwd();
        if (false === $workingDir) {
            // @codeCoverageIgnoreStart
            StatusException::status(1, 'fatal: obtain working directory');
            // @codeCoverageIgnoreEnd
        }

        return $workingDir;
    }

    /**
     * @throws InvalidArgumentException
     * @throws StatusException
     * @throws ArgsException
     * @return string basename for bitbucket-pipelines.yml
     */
    private function parseBasename()
    {
        $args = $this->arguments;

        $basename = $args->getOptionArgument('basename', self::BBPL_BASENAME);
        if (!Lib::fsIsBasename($basename)) {
            StatusException::status(1, sprintf("not a basename: '%s'", $basename));
        }

        return $basename;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ArgsException
     * @throws StatusException
     * @return string deploy mode ('copy', 'mount')
     */
    private function parseDeployMode()
    {
        $args = $this->arguments;

        $deployMode = $args->getOptionArgument('deploy', 'copy');

        if (!in_array($deployMode, array('mount', 'copy'), true)) {
            StatusException::status(
                1,
                sprintf("unknown deploy mode '%s'\n", $deployMode)
            );
        }

        return $deployMode;
    }

    /**
     * @param array $inherit from this environment
     * @param Runner\Reference $reference
     * @param string $workingDir
     * @throws InvalidArgumentException
     * @throws ArgsException
     * @return Env
     */
    private function parseEnv(array $inherit, $reference, $workingDir)
    {
        $args = $this->arguments;

        Lib::v($inherit['BITBUCKET_REPO_SLUG'], basename($workingDir));

        $env = Env::create($inherit);
        $env->addReference($reference);

        $noDotEnvFiles = $args->hasOption('no-dot-env-files');
        $noDotEnvDotDist = $args->hasOption('no-dot-env-dot-dist');

        if (false === $noDotEnvFiles) {
            $filesToCollect = array();
            if (false === $noDotEnvDotDist) {
                $filesToCollect[] = $workingDir . '/.env.dist';
            }
            $filesToCollect[] = $workingDir . '/.env';
            $env->collectFiles($filesToCollect);
        }

        $env->collect($args, array('e', 'env', 'env-file'));

        return $env;
    }

    /**
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     * @throws StatusException
     * @throws ArgsException
     * @return string file
     */
    private function parseFile($basename, &$workingDir)
    {
        $args = $this->arguments;

        /** @var string $file as bitbucket-pipelines.yml to process */
        $file = $args->getOptionArgument('file', null);
        if (null === $file && null !== $file = Lib::fsFileLookUp($basename, $workingDir)) {
            $buffer = dirname($file);
            if ($buffer !== $workingDir) {
                $this->changeWorkingDir($buffer);
                $workingDir = $this->getWorkingDirectory();
            }
        }

        if (!strlen($file)) {
            StatusException::status(1, 'file can not be empty');
        }

        return $file;
    }

    /**
     * @param string $basename
     * @param string $workingDir
     * @throws InvalidArgumentException
     * @throws ArgsException
     * @throws StatusException
     * @return string path
     */
    private function parsePath($basename, &$workingDir)
    {
        $file = $this->parseFile($basename, $workingDir);

        if ($file !== $basename && self::BBPL_BASENAME !== $basename) {
            $this->verbose('info: --file overrides non-default --basename');
        }

        /** @var string $path full path as bitbucket-pipelines.yml to process */
        $path = Lib::fsIsAbsolutePath($file)
            ? $file
            : $workingDir . '/' . $file;

        if (!is_file($path) && !is_readable($path)) {
            StatusException::status(1, sprintf('not a readable file: %s', $file));
        }

        $this->verbose(sprintf("info: pipelines file is '%s'", $path));

        return $path;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ArgsException
     * @return string
     */
    private function parsePrefix()
    {
        $args = $this->arguments;

        $prefix = $args->getOptionArgument('prefix', 'pipelines');
        if (!preg_match('~^[a-z]{3,}$~', $prefix)) {
            ArgsException::__(sprintf("invalid prefix: '%s'", $prefix));
        }

        return $prefix;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ArgsException
     * @return Runner\Reference
     */
    private function parseReference()
    {
        $trigger = $this->arguments->getOptionArgument('trigger');

        return Runner\Reference::create($trigger);
    }

    /**
     * give error about unknown option, show usage and exit status of 1
     *
     * @throws ArgsException
     */
    private function parseRemainingOptions()
    {
        $option = $this->arguments->getFirstRemainingOption();

        if ($option) {
            ArgsException::__(
                sprintf('unknown option: %s', $option)
            );
        }
    }

    /**
     * @throws InvalidArgumentException
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
     * @return string current working directory
     */
    private function parseWorkingDir()
    {
        $args = $this->arguments;

        $buffer = $args->getOptionArgument('working-dir', false);

        if (false !== $buffer) {
            $this->changeWorkingDir($buffer);
        }

        return $this->getWorkingDirectory();
    }

    /**
     * @param string $directory
     * @throws StatusException
     */
    private function changeWorkingDir($directory)
    {
        $this->verbose(
            sprintf('info: changing working directory to %s', $directory)
        );

        $result = chdir($directory);
        if (false === $result) {
            StatusException::status(
                2,
                sprintf('fatal: change working directory to %s', $directory)
            );
        }
    }

    /**
     * Obtain pipeline to run from file while handling error output
     *
     * @param File $pipelines
     * @param $pipelineId
     * @param FileOptions $fileOptions
     * @throws \Ktomk\Pipelines\File\ParseException
     * @throws StatusException
     * @return Pipeline on success
     */
    private function getRunPipeline(File $pipelines, $pipelineId, FileOptions $fileOptions)
    {
        $this->verbose(sprintf("info: running pipeline '%s'", $pipelineId));

        try {
            $pipeline = $pipelines->getById($pipelineId);
        } catch (File\ParseException $e) {
            $this->error(sprintf("pipelines: error: pipeline id '%s'", $pipelineId));

            throw $e;
        } catch (InvalidArgumentException $e) {
            $this->error(sprintf("pipelines: pipeline '%s' unavailable", $pipelineId));
            $this->info('Pipelines are:');
            $fileOptions->showPipelines($pipelines);
            StatusException::status(1);
        }

        if (!$pipeline) {
            StatusException::status(1, 'no pipeline to run!');
        }

        return $pipeline;
    }

    private function error($message)
    {
        $this->streams->err(
            sprintf("%s\n", $message)
        );
    }

    private function info($message)
    {
        $this->streams->out(
            sprintf("%s\n", $message)
        );
    }

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
     * @param $deployMode
     * @return bool|int
     */
    private function getRunFlags(KeepOptions $keep, $deployMode)
    {
        $flags = Runner::FLAGS;
        if ($keep->errorKeep) {
            $flags |= Runner::FLAG_KEEP_ON_ERROR;
        } elseif ($keep->keep) {
            $flags &= ~(Runner::FLAG_DOCKER_KILL | Runner::FLAG_DOCKER_REMOVE);
        }

        if ('copy' === $deployMode) {
            $flags |= Runner::FLAG_DEPLOY_COPY;
        }

        return $flags;
    }
}
