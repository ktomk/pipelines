<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Exception;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File;
use Ktomk\Pipelines\Runner;
use Ktomk\Pipelines\Runner\Env;
use Ktomk\Pipelines\Step;

class App
{
    const BBPL_BASENAME = 'bitbucket-pipelines.yml';

    const VERSION = '@.@.@';

    /**
     * @var bool whether version has been shown or not
     * @see App::showVersion()
     */
    private $versionShown = false;

    /**
     * @var Args|string[]
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
        if (!$this->versionShown) {
            $version = $this->translateSourceVersion(self::VERSION);
            $this->info(sprintf('pipelines version %s', $version));
            $this->versionShown = true;
        }

        return 0;
    }

    /**
     * obtain utility version for the source edition
     *
     * @param string $version
     * @return string
     */
    private function translateSourceVersion($version)
    {
        // version is build version
        if ('@.' . '@.@' !== $version) {
            return $version; // @codeCoverageIgnore
        }

        // as composer module
        $installedFile = __DIR__ . '/../../../../composer/installed.json';
        if (is_file($installedFile) && is_readable($installedFile)) {
            // @codeCoverageIgnoreStart
            $buffer = file_get_contents($installedFile);
            $struct = json_decode($buffer);
            foreach ((array) $struct as $package) {
                if (!isset($package->name) || $package->name !== 'ktomk/pipelines') {
                    continue;
                }
                if (!isset($package->version)) {
                    break;
                }
                return $package->version;
            }
            // @codeCoverageIgnoreEnd
        }

        // as git repository
        $buffer = exec(sprintf(
            'cd %s && echo $(git describe --tags --always --first-parent 2>/dev/null)$(git diff-index --quiet HEAD -- 2>/dev/null || echo +)',
            escapeshellarg(__DIR__)
        ));
        if ($buffer !== '+') {
            return $buffer;
        }

        return $version; // @codeCoverageIgnore
    }

    private function showUsage()
    {
        $this->streams->out(<<<EOD
usage: pipelines [<options>...] [--version | [-h | --help]]
       pipelines [-v | --verbose] [--working-dir <path>] [--keep] 
                 [--prefix <prefix>] [--basename <basename>] 
                 [--file <path>] [--dry-run] [--no-run] [--list]
                 [--deploy mount | copy ] [--show] [--images]
                 [--pipeline <id>]
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
    --version             show version information

Common options
    --keep                keep docker containers. default is to
                          kill and remove containers after each
                          pipeline step
    --prefix <prefix>     use a different prefix for container
                          names, default is 'pipelines'
    --basename <basename> set basename for pipelines file,
                          default is 'bitbucket-pipelines.yml'
    --file <path>         path to the pipelines file, overrides
                          looking up the <basename> file from 
                          the current working directory
    --working-dir <path>  run as if pipelines was started in
                          <path>
    --deploy mount|copy   how files from the working directory
                          are placed into the pipeline container:
                          mount    (default) the working dir is
                                 mounted. fastest, no isolation
                          copy     working directory is copied
                                 into the container. slower,
                                 stronger isolation as the
                                 pipeline scripts can change
                                 all files without side-effects
                                 in the working directory
    --list                list pipeline <id>s in file and exit
    --show                show information about pipelines in
                          file and exit
    --images              list all images in file, in order
                          of use, w/o duplicate names and exit
    --pipeline <id>       run pipeline with <id>, see --list
    --no-run              do not run the pipeline
    --dry-run             do not invoke docker or run containers,
                          with --verbose shows the commands that
                          would have run w/o the --dry-run flag

Docker container maintenance options
      usage might leave containers on the system. either by 
      interrupting a running pipeline step or by keeping the
      running containers (--keep).
      
      pipelines uses a prefix followed by '-' and a UUID for 
      container names. the prefix is either 'pipelines' or the
      one set by --prefix <prefix>.
      
      three options are built-in to monitor and deal with 
      leftovers. if one or more of these are given, the following
      operations are executed in the order from top to down:

    --docker-list         list prefixed containers
    --docker-clean        remove (non-running) containers with 
                          pipelines prefix
    --docker-kill         kills prefixed containers

Less documented options
    --debug               flag for trouble-shooting fatal errors

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
            $message = sprintf('fatal: pipelines file: %s', $e->getMessage());
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

        if ($this->verbose = $args->hasOption(array('v', 'verbose'))) {
            $this->showVersion();
        };

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

        if ($dryRun = $args->hasOption('dry-run')) {
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

        /** @var string $basename for bitbucket-pipelines.yml */
        $basename = $args->getOptionArgument('basename', self::BBPL_BASENAME);
        // FIXME: must actually be a basename to prevent accidental traversal
        if (!strlen($basename)) {
            $this->error('Empty basename');
            return 1;
        }

        if (false !== $buffer = $args->getOptionArgument('working-dir', false)) {
            $result = chdir($buffer);
            if ($result === false) {
                $this->error(sprintf('fatal: change working directory to %s', $buffer));
                return 2;
            }
        }

        $workingDir = getcwd();
        if ($workingDir === false) {
            // @codeCoverageIgnoreStart
            $this->error('fatal: obtain working directory');
            return 1;
            // @codeCoverageIgnoreEnd
        }

        /** @var string $file as bitbucket-pipelines.yml to process */
        $file = $args->getOptionArgument('file', $basename);
        if (!strlen($file)) {
            $this->error('File can not be empty');
            return 1;
        }
        if ($file !== $basename && $basename !== self::BBPL_BASENAME) {
            $this->verbose('info: --file overrides non-default --basename');
        }

        // TODO: obtain project dir information etc. from VCS, also traverse for basename file
        // $vcs = new Vcs();

        /** @var string $path full path as bitbucket-pipelines.yml to process */
        // FIXME: try a variant with PHP stream wrapper prefixes support
        $path = ('/' === $file[0])
            ? $file
            : $workingDir . '/' . $file;

        if (!is_file($path) && !is_readable($path)) {
            $this->error(sprintf('Not a readable file: %s', $file));
            return 1;
        }
        unset($file);

        $noRun = $args->hasOption('no-run');

        $deployMode = $args->getOptionArgument('deploy', 'mount');
        if (!in_array($deployMode, array('mount', 'copy'))) {
            $this->error(sprintf("Unknown deploy mode '%s'\n", $deployMode));
            return 1;
        }

        $show = $args->hasOption('show');
        $list = $args->hasOption('list');
        $images = $args->hasOption('images');

        $this->verbose(sprintf("info: pipelines file is '%s'", $path));

        $pipelines = File::createFromFile($path);

        if ($images) return $this->showImages($pipelines);
        if ($show) return $this->showPipelines($pipelines);
        if ($list) return $this->showPipelineIds($pipelines);

        ###

        $reference = Runner\Reference::create(
            $args->getOptionArgument('trigger')
        );

        $env = Env::create($_SERVER);
        $env->addReference($reference);

        $pipelineId = $pipelines->searchIdByReference($reference) ?: 'default';

        $pipelineId = $args->getOptionArgument('pipeline', $pipelineId);

        // --verbatim show only errors for own runner actions, show everything from pipeline verbatim
        $streams = $this->streams;
        if ($args->hasOption('verbatim')) {
            $streams = new Streams();
            $streams->copyHandle($this->streams,2);
        }

        if ($option = $args->getFirstRemainingOption()) {
            $this->error("Unknown option: $option");
            $this->showUsage();
            return 1;
        }

        ###

        $this->verbose(sprintf("info: running pipeline '%s'", $pipelineId));

        try {
            $pipeline = $pipelines->getById($pipelineId);
        } catch (File\ParseException $e) {
            $this->error(sprintf("error: pipeline id '%s'", $pipelineId));
            throw $e;
        } catch (\InvalidArgumentException $e) {
            $this->error(sprintf("Pipeline '%s' unavailable", $pipelineId));
            $this->info('Pipelines are:');
            $this->showPipelines($pipelines);
            return 1;
        }

        if (!$pipeline) {
            $this->error("error: no pipeline to run!");
            return 1;
        }

        $dir = $workingDir;
        $flags = Runner::FLAGS;
        if ($keep) {
            $flags &= ~(Runner::FLAG_DOCKER_KILL | Runner::FLAG_DOCKER_REMOVE);
        }

        if ($deployMode === 'copy') {
            $flags |= Runner::FLAG_DEPLOY_COPY;
        }

        $runner = new Runner($prefix, $dir, $exec, $flags, $env, $streams);
        if ($noRun) {
            $this->verbose('info: not running the pipeline per --no-run option');
            $status = 0;
        } else {
            $status = $runner->run($pipeline, $env);
        }

        return $status;
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
        $this->verbose && $this->info($message);
    }

    private function showImages(File $pipelines)
    {
        /**
         * @param File $file
         * @return array|Step[]
         */
        $iter = function (File $file) {
            $ids = $file->getPipelineIds();
            $return = array();
            foreach ($ids as $id) {
                foreach ($file->getById($id)->getSteps() as $index => $step) {
                    $return["$id:/step/$index"] = $step;
                }
            }

            return $return;
        };

        $images = array();
        foreach ($iter($pipelines) as $step) {
            $image = $step->getImage();
            $images[$image] = $image;
        }

        foreach ($images as $image) {
            $this->info($image);
        }

        return 0;
    }

    private function showPipelineIds(File $pipelines)
    {
        foreach ($pipelines->getPipelineIds() as $id) {
            $this->info($id);
        }

        return 0;
    }

    /**
     * @param $pipelines
     * @return int
     */
    private function showPipelines(File $pipelines)
    {
        $errors = 0;
        $table = array(array('PIPELINE ID', 'IMAGES', 'STEPS'));
        foreach ($pipelines->getPipelineIds() as $id) {
            $images = array();
            $names = array();

            try {
                $pipeline = $pipelines->getById($id);
                $steps = $pipeline->getSteps();
            } catch (Exception $e) {
                $errors++;
                $table[] = array($id, 'ERROR', $e->getMessage());
                continue;
            }

            foreach ($steps as $step) {
                $image = $step->getImage();
                if ($image !== $pipelines::DEFAULT_IMAGE) {
                    $images[] = $image;
                }
                $name = $step->getName();
                $name && $names[] = $name;
            }
            $images = $images ? implode(', ', $images) : '';
            $steps = sprintf('%d%s', count($steps), $names ? ' ("' . implode('""; "', $names) . '")' : '');
            $table[] = array($id, $images, $steps);
        }

        $this->textTable($table);

        return $errors ? 1 : 0;
    }

    private function textTable(array $table)
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

        foreach ($table as $row) {
            $line = '';
            foreach ($row as $index => $column) {
                $len = strlen($column);
                $index && $line .= "    ";
                $line .= $column;
                $line .= str_repeat(' ', $sizes[$index] - $len);
            }
            $this->info($line);
        }
    }
}
