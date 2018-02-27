<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Exception;
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
     * @return App
     */
    static function create()
    {
        $streams = Streams::create();

        return new self($streams);
    }

    public function __construct(Streams $streams)
    {
        $this->streams = $streams;
    }

    private function showVersion()
    {
        $version = Version::resolve(self::VERSION);
        $this->info(sprintf('pipelines version %s', $version));

        return 0;
    }

    private function showUsage()
    {
        $this->streams->out(<<<EOD
usage: pipelines [<options>...] [--version | [-h | --help]]
       pipelines [-v | --verbose] [--working-dir <path>] [--[no-]keep]
                 [--prefix <prefix>] [--basename <basename>]
                 [[-e | --env] <variable>] [--env-file <path>]
                 [--file <path>] [--dry-run] [--no-run] [--list]
                 [--deploy mount | copy ] [--show] [--images]
                 [--pipeline <id>] [--trigger <ref>] [--verbatim]
       pipelines [-v | --verbose] [--dry-run] [--docker-list]
                 [--docker-kill] [--docker-clean]

EOD
        );
    }

    private function showHelp()
    {
        $this->showUsage();
        $this->streams->out(<<<EOD

    -h, --help            show usage and help information
    -v, --verbose         show commands executed
    --version             show version information only and exit

Common options
    --basename <basename> set basename for pipelines file,
                          default is 'bitbucket-pipelines.yml'
    --deploy mount|copy   how files from the working directory
                          are placed into the pipeline container:
                          copy     (default) working dir is
                                 copied into the container.
                                 stronger isolation as the
                                 pipeline scripts can change
                                 all files without side-effects
                                 in the working directory
                          mount    the working directory is
                                 mounted. fastest, no isolation
    -e, --env <variable>  pass or set an environment variables
                          for the docker container
    --env-file <path>     pass variables from environment file
                          to the docker container
    --file <path>         path to the pipelines file, overrides
                          looking up the <basename> file from
                          the current working directory
    --[no-]keep           (do not) keep docker containers.
                          default is to kill and remove
                          containers after each pipeline step
                          unless the pipeline step failed. then
                          the non-zero exit status is given and
                          an error message showing the container
                          id of the kept container
    --trigger <ref>       build trigger, <ref> can be of either
                          tag:<name>, branch:<name> or
                          bookmark:<name>. used in determination
                          of the pipeline to run
    --pipeline <id>       run pipeline with <id>, see --list
    --verbatim            only give verbatim output of the
                          pipeline, no other information around
                          like which step currently executes
    --working-dir <path>  run as if pipelines was started in
                          <path>

Run control options
    --dry-run             do not invoke docker or run containers,
                          with --verbose shows the commands that
                          would have run w/o the --dry-run flag
    --no-run              do not run the pipeline

File information options
    --images              list all images in file, in order
                          of use, w/o duplicate names and exit
    --list                list pipeline <id>s in file and exit
    --show                show information about pipelines in
                          file and exit

Docker container maintenance options
      usage might leave containers on the system. either by
      interrupting a running pipeline step or by keeping the
      running containers (--keep).

      pipelines uses a prefix followed by '-' and a UUID for
      container names. the prefix is either 'pipelines' or the
      one set by --prefix <prefix>.

      three options are built-in to monitor and interact with
      leftovers. if one or more of these are given, the following
      operations are executed in the order from top to down:

    --docker-list         list prefixed containers
    --docker-kill         kills prefixed containers
    --docker-clean        remove (non-running) containers with
                          pipelines prefix

Less common options
    --debug               flag for trouble-shooting fatal errors
    --prefix <prefix>     use a different prefix for container
                          names, default is 'pipelines'

EOD
        );
        return 0;
    }

    /**
     * @param array $arguments including the utility name in the first argument
     * @return int 0-255
     */
    public function main(array $arguments)
    {
        $this->arguments = Args::create($arguments);
        $this->debug = $this->arguments->hasOption('debug');

        try {
            $status = $this->run();
        } catch (ArgsException $e) {
            $status = $e->getCode();
            $message = $e->getMessage();
            $this->error($message);
            $this->showUsage();
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
     * @return int|null
     * @throws ArgsException
     */
    public function run()
    {
        $args = $this->arguments;

        $this->verbose = $args->hasOption(array('v', 'verbose'));

        # quickly handle version
        if ($args->hasOption('version')) {
            return $this->showVersion();
        }

        # quickly handle help
        if ($args->hasOption(array('h', 'help'))) {
            return $this->showHelp();
        }

        $prefix = $args->getOptionArgument('prefix', 'pipelines');
        if (!preg_match('~^[a-z]{3,}$~', $prefix)) {
            ArgsException::__(sprintf("Invalid prefix: '%s'", $prefix));
        }

        $debugPrinter = null;
        if ($this->verbose) {
            $debugPrinter = $this->streams;
        }
        $exec = new Exec($debugPrinter);

        if ($args->hasOption('dry-run')) {
            $exec->setActive(false);
        }

        if (
            null !== $status
                = DockerOptions::bind($args, $exec, $prefix, $this->streams)->run()
        ) {
            return $status;
        }

        /** @var bool $keep containers */
        $keep = $args->hasOption('keep');
        /** @var bool $noKeep do not keep on errors */
        $noKeep = $args->hasOption('no-keep');
        if ($keep && $noKeep) {
            $this->error('pipelines: --keep and --no-keep are exclusive');
            return 1;
        }

        /** @var string $basename for bitbucket-pipelines.yml */
        $basename = $args->getOptionArgument('basename', self::BBPL_BASENAME);
        if (!Lib::fsIsBasename($basename)) {
            $this->error(sprintf("pipelines: not a basename: '%s'", $basename));
            return 1;
        }

        if (false !== $buffer = $args->getOptionArgument('working-dir', false)) {
            $result = chdir($buffer);
            if ($result === false) {
                $this->error(sprintf('pipelines: fatal: change working directory to %s', $buffer));
                return 2;
            }
        }

        $workingDir = getcwd();
        if ($workingDir === false) {
            // @codeCoverageIgnoreStart
            $this->error('pipelines: fatal: obtain working directory');
            return 1;
            // @codeCoverageIgnoreEnd
        }

        /** @var string $file as bitbucket-pipelines.yml to process */
        $file = $args->getOptionArgument('file', $basename);
        if (!strlen($file)) {
            $this->error('pipelines: file can not be empty');
            return 1;
        }
        if ($file !== $basename && $basename !== self::BBPL_BASENAME) {
            $this->verbose('info: --file overrides non-default --basename');
        }

        // TODO: obtain project dir information etc. from VCS, also traverse for basename file
        // $vcs = new Vcs();

        /** @var string $path full path as bitbucket-pipelines.yml to process */
        $path = Lib::fsIsAbsolutePath($file)
            ? $file
            : $workingDir . '/' . $file;

        if (!is_file($path) && !is_readable($path)) {
            $this->error(sprintf('pipelines: not a readable file: %s', $file));
            return 1;
        }
        unset($file);

        $noRun = $args->hasOption('no-run');

        $deployMode = $args->getOptionArgument('deploy', 'copy');
        if (!in_array($deployMode, array('mount', 'copy'))) {
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
            $this->error("pipelines: unknown option: $option");
            $this->showUsage();
            return 1;
        }

        ###

        $pipeline = $this->getRunPipeline($pipelines, $pipelineId, $fileOptions);
        if (!$pipeline instanceof Pipeline) {
            return $pipeline;
        }

        $flags = $this->getRunFlags($keep, $noKeep, $deployMode);

        $runner = new Runner($prefix, $workingDir, $exec, $flags, $env, $streams);
        if ($noRun) {
            $this->verbose('info: not running the pipeline per --no-run option');
            $status = 0;
        } else {
            $status = $runner->run($pipeline);
        }

        return $status;
    }

    /**
     * Obtain pipeline to run from file while handling error output
     *
     * @param File $pipelines
     * @param $pipelineId
     * @param FileOptions $fileOptions
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
        } catch (\InvalidArgumentException $e) {
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
     * @param bool $keep
     * @param bool $noKeep
     * @param $deployMode
     * @return bool|int
     */
    private function getRunFlags($keep, $noKeep, $deployMode)
    {
        $flags = Runner::FLAGS;
        if ($keep) {
            $flags &= ~(Runner::FLAG_DOCKER_KILL | Runner::FLAG_DOCKER_REMOVE);
        } elseif ($noKeep) {
            $flags &= ~Runner::FLAG_KEEP_ON_ERROR;
        }

        if ($deployMode === 'copy') {
            $flags |= Runner::FLAG_DEPLOY_COPY;
        }
        return $flags;
    }
}
