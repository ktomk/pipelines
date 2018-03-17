<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Exception;
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

class App
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
     * @var bool
     */
    private $debug = false;

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
        $this->debug = $args->hasOption('debug');
        $this->verbose = $args->hasOption(array('v', 'verbose'));
        $this->arguments = $args;

        try {
            $status = $this->run();
        } catch (ArgsException $e) {
            $status = $e->getCode();
            $message = $e->getMessage();
            $this->error($message);
            $this->help->showUsage();
        } catch (StatusException $e) {
            $status = $e->getCode();
            if (0 !== $status && '' !== $message = $e->getMessage()) {
                $this->error(sprintf('pipelines: %s', $message));
            }
        } catch (File\ParseException $e) {
            $status = 2;
            $message = sprintf('pipelines: file parse error: %s', $e->getMessage());
            $this->error($message);
        } catch (Exception $e) { // @codeCoverageIgnoreStart
            // catch unexpected exceptions for user-friendly message
            $status = 2;
            $message = sprintf('fatal: %s', $e->getMessage());
            $this->error($message);
            // @codeCoverageIgnoreEnd
        }

        if (isset($e) && $this->debug) {
            for (; $e; $e = $e->getPrevious()) {
                $this->error('--------');
                $this->error(sprintf("class....: %s", get_class($e)));
                $this->error(sprintf("message..: %s", $e->getMessage()));
                $this->error(sprintf("code.....: %s", $e->getCode()));
                $this->error(sprintf("file.....: %s", $e->getFile()));
                $this->error(sprintf("line.....: %s", $e->getLine()));
                $this->error('backtrace:');
                $this->error($e->getTraceAsString());
            }
            $this->error('--------');
        }

        return $status;
    }

    /**
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     * @throws \Ktomk\Pipelines\File\ParseException
     * @throws ArgsException
     * @throws StatusException
     * @return null|int
     */
    public function run()
    {
        $this->helpRun();

        $prefix = $this->parsePrefix();

        $exec = $this->parseExec();

        $args = $this->arguments;

        DockerOptions::bind($args, $exec, $prefix, $this->streams)->run();

        $keep = KeepOptions::bind($args)->run();

        $basename = $this->parseBasename();

        $workingDir = $this->parseWorkingDir();

        $file = $this->parseFile($basename, $workingDir);

        $path = $this->parsePath($basename, $file, $workingDir);

        unset($file);

        // TODO: obtain project dir information etc. from VCS
        // $vcs = new Vcs();

        $noRun = $args->hasOption('no-run');

        $deployMode = $args->getOptionArgument('deploy', 'copy');
        if (!in_array($deployMode, array('mount', 'copy'), true)) {
            $this->error(sprintf("pipelines: unknown deploy mode '%s'\n", $deployMode));

            return 1;
        }

        $this->verbose(sprintf("info: pipelines file is '%s'", $path));

        $pipelines = File::createFromFile($path);

        $fileOptions = FileOptions::bind($args, $this->streams, $pipelines);
        if (null !== $status = $fileOptions->run()) {
            return $status;
        }

        ###

        $reference = Runner\Reference::create(
            $args->getOptionArgument('trigger')
        );

        $env = Env::create($_SERVER);
        $env->addReference($reference);
        $env->collectFiles(array(
            $workingDir . '/.env.dist',
            $workingDir . '/.env',
        ));
        $env->collect($args, array('e', 'env', 'env-file'));

        $pipelineId = $pipelines->searchIdByReference($reference) ?: 'default';

        $pipelineId = $args->getOptionArgument('pipeline', $pipelineId);

        // --verbatim show only errors for own runner actions, show everything from pipeline verbatim
        $streams = $this->streams;
        if ($args->hasOption('verbatim')) {
            $streams = new Streams();
            $streams->copyHandle($this->streams, 2);
        }

        if ($option = $args->getFirstRemainingOption()) {
            $this->error("pipelines: unknown option: ${option}");
            $this->help->showUsage();

            return 1;
        }

        ###

        $pipeline = $this->getRunPipeline($pipelines, $pipelineId, $fileOptions);
        if (!$pipeline instanceof Pipeline) {
            return $pipeline;
        }

        $flags = $this->getRunFlags($keep, $deployMode);

        $runner = new Runner($prefix, $workingDir, $exec, $flags, $env, $streams);
        if ($noRun) {
            $this->verbose('info: not running the pipeline per --no-run option');
            $status = 0;
        } else {
            $status = $runner->run($pipeline);
            if (0 !== $status) {
                $this->streams->out(
                    sprintf("exit status: %d\n", $status)
                );
            }
        }

        return $status;
    }

    /**
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
     * @param string $basename
     * @param string $workingDir
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
     * @param string $file
     * @param string $workingDir
     * @throws StatusException
     * @return string path
     */
    private function parsePath($basename, $file, $workingDir)
    {
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

        return $path;
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
     * @throws ArgsException
     * @return string
     */
    private function parsePrefix()
    {
        $args = $this->arguments;

        $prefix = $args->getOptionArgument('prefix', 'pipelines');
        if (!preg_match('~^[a-z]{3,}$~', $prefix)) {
            ArgsException::__(sprintf("Invalid prefix: '%s'", $prefix));
        }

        return $prefix;
    }

    /**
     * @throws StatusException
     */
    private function helpRun()
    {
        $args = $this->arguments;

        $status = null;

        # quickly handle version
        $help = $this->help;
        if ($args->hasOption('version')) {
            StatusException::status($help->showVersion());
        }

        # quickly handle help
        if ($args->hasOption(array('h', 'help'))) {
            StatusException::status($help->showHelp());
        }
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
     * @return int|Pipeline on success, integer on error as exit status
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

            return 1;
        }

        if (!$pipeline) {
            $this->error("pipelines: no pipeline to run!");

            return 1;
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
            $flags &= ~Runner::FLAG_KEEP_ON_ERROR;
        } elseif ($keep->keep) {
            $flags &= ~(Runner::FLAG_DOCKER_KILL | Runner::FLAG_DOCKER_REMOVE);
        }

        if ('copy' === $deployMode) {
            $flags |= Runner::FLAG_DEPLOY_COPY;
        }

        return $flags;
    }
}
