<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Exception;
use InvalidArgumentException;
use Ktomk\Pipelines\File;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\Runner;
use Ktomk\Pipelines\Runner\Env;
use Ktomk\Pipelines\Step;

class App
{
    const BBPL_BASENAME = 'bitbucket-pipelines.yml';

    const VERSION = '@.@.@';

    /**
     * @var Args|string[]
     */
    private $arguments;

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
        return new self();
    }

    private function showVersion()
    {
        $this->info(sprintf('pipelines version %s', self::VERSION));

        return 0;
    }

    private function showUsage()
    {
        echo <<<EOD
usage: pipelines [<options>...] [--version | [-h | --help]]
       pipelines [-v | --verbose] [--working-dir <path>] [--keep] 
                 [--prefix <prefix>] [--basename <basename>] 
                 [--file <path>] [--dry-run] [--no-run] [--list]
                 [--show] [--images] [--pipeline <id>]
       pipelines [-v | --verbose] [--docker-list] [--docker-kill] 
                 [--docker-clean]

EOD;
    }

    private function showHelp()
    {
        $this->showUsage();
        echo <<<EOD

    -h, --help            show usage and help information
    -v, --verbose         show commands executed
    --version             show version information

Common options
    --keep                keep docker containers. default is to
                          kill and remove containers after each
                          pipeline step
    --prefix <prefix>     use a different prefix for container
                          names, default is 'pipeline'
    --basename <basename> set basename for pipelines file, 
                          default is 'bitbucket-pipelines.yml'
    --file <path>         path to the pipelines file, overrides
                          looking up the <basename> file from 
                          the current working directory
    --working-dir <path>  run as if pipelines was started in
                          <path>
    --list                list pipeline <id>s in file and exit
    --show                show information about pipelines in
                          file and exit
    --images              list all images in file, on order 
                          of use, w/o duplicate names and exit
    --pipeline <id>       run pipeline with <id>, see --list
    --no-run              do not run the pipeline
    --dry-run             do not start containers and run in 
                          them, with --verbose to show the 
                          commands that would be run

Docker container maintenance options
      usage might leave containers on the system. either by 
      interrupting a running pipeline step or by keeping the
      running containers (--keep).
      
      pipelines uses a prefix followed by '-' and a UUID for 
      container names. the prefix is either 'pipeline' or the
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

EOD;
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
        } catch (InvalidArgumentException $e) {
            $status = 2;
            $message = sprintf('fatal: %s', $e->getMessage());
            $this->error($message);
        } catch (Exception $e) {
            $status = 2;
            $message = $e->getMessage();
            $this->error($message);
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

        $debugPrinter = null;
        if ($this->verbose) {
            $debugPrinter = function ($message) {
                $this->info($message);
            };
        }

        $prefix = $args->getOptionArgument('prefix', 'pipeline');
        if (!preg_match('~^[a-z]{3,}$~', $prefix)) {
            ArgsException::give(sprintf("error: invalid prefix: '%s'", $prefix));
        }

        $exec = new Exec($debugPrinter);
        if ($array = $this->dockerOptions($args, $exec, $prefix) and $array[0]) {
            return $array[1];
        }

        /** @var bool $keep containers */
        $keep = $args->hasOption('keep');

        /** @var string $basename for bitbucket-pipelines.yml */
        $basename = $args->getOptionArgument('basename', self::BBPL_BASENAME);
        if (!strlen($basename)) {
            $this->error(sprintf('Basename can not be empty'));
            return 1;
        }

        if (false !== $buffer = $args->getOptionArgument('working-dir', false)) {
            $result = chdir($buffer);
            if ($result === false) {
                $this->error(sprintf('fatal: could not change working directory to: %s', $buffer));
                return 1;
            }
        }

        $workingDir = getcwd();
        if ($workingDir === false) {
            $this->error('fatal: failed to obtain working directory');
            return 1;
        }

        /** @var string $file as bitbucket-pipelines.yml to process */
        $file = $args->getOptionArgument('file', $basename);
        if (!strlen($file)) {
            $this->error(sprintf('File can not be empty'));
            return 1;
        }
        if ($this->verbose && $file !== $basename && $basename !== self::BBPL_BASENAME) {
            $this->verbose("info: --file overrides non-default --basename");
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
        if ($dryRun = $args->hasOption('dry-run')) {
            $exec->setActive(false);
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

        $env = Env::create();
        $env->addReference($reference);

        $pipelineId = $pipelines->searchIdByReference($reference) ?: 'default';

        $pipelineId = $args->getOptionArgument('pipeline', $pipelineId);

        if ($option = $args->getFirstRemainingOption()) {
            $this->error("Unknown option: $option");
            $this->showUsage();
            return 1;
        }

        ###

        $this->verbose(sprintf("info: running pipeline '%s'", $pipelineId));

        try {
            $pipeline = $pipelines->getById($pipelineId);
        } catch (\InvalidArgumentException $e) {
            $this->error(sprintf("error: no pipeline id '%s'", $pipelineId));
            $this->info('pipelines are:');
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
            $flags &= ~(Runner::FLAG_KILL | Runner::FLAG_REMOVE);
        }
        $runner = new Runner($prefix, $dir, $exec, $flags, $env);
        if ($noRun) {
            $this->verbose('info: not running the pipeline per --no-run option');
            $status = 0;
        } else {
            $status = $runner->run($pipeline, $env);
        }

        return $status;
    }

    /**
     * Process docker related options
     *
     * --docker-list  - list containers
     * --docker-kill  - kill (running) containers
     * --docker-clean - remove (stopped) containers
     *
     * @param Args $args
     * @param $exec
     * @param $prefix
     * @return array|null if any of the commands were execute array with number of commands, last status
     */
    private function dockerOptions(Args $args, Exec $exec, $prefix)
    {
        $count = 0;
        $status = 0;

        if (!$status && $args->hasOption('docker-list')) {
            $count++;
            $status = $exec->pass(
                'docker ps --no-trunc -a',
                array('--filter', "name=^/$prefix-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$")
            );
        }

        $hasKill = $args->hasOption('docker-kill');
        $hasClean = $args->hasOption('docker-clean');

        $ids = null;
        if ($hasClean || $hasKill) {
            $count++;
            $status = $exec->capture(
                'docker',
                array('ps', '-qa', '--filter', "name=^/$prefix-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$"),
                $result
            );

            $status || $ids = Lib::lines($result);
        }

        if (!$status && $hasKill) {
            $count++;
            $status = $exec->capture(
                'docker',
                array('ps', '-q', '--filter', "name=^/$prefix-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$"),
                $result
            );
            $running = $status ? $ids : Lib::lines($result);
            if ($running) {
                $status = $exec->pass('docker', Lib::merge('kill', $running));
            } else {
                $this->info("no containers to kill");
            }
        }

        if (!$status && $hasClean) {
            $count++;
            if ($ids) {
                $status = $exec->pass('docker', Lib::merge('rm', $ids));
            } else {
                $this->info("no containers to remove");
            }
        }

        return $count ? array($count, $status) : null;
    }

    private function error($message)
    {
        fprintf(STDERR, "%s\n", $message);
    }

    private function info($message)
    {
        fprintf(STDOUT, "%s\n", $message);
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
            printf("%s\n", $image);
        }

        return 0;
    }

    private function showPipelineIds(File $pipelines)
    {
        foreach ($pipelines->getPipelineIds() as $id) {
            printf("%s\n", $id);
        }

        return 0;
    }

    /**
     * @param $pipelines
     * @return int
     */
    private function showPipelines(File $pipelines)
    {
        $table = array(array('PIPELINE ID', 'IMAGES', 'STEPS'));
        foreach ($pipelines->getPipelineIds() as $id) {
            $images = array();
            $names = array();

            try {
                $pipeline = $pipelines->getById($id);
                $steps = $pipeline->getSteps();
            } catch (Exception $e) {
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

        return 0;
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
            foreach ($row as $index => $column) {
                $len = strlen($column);
                $index && printf("    ");
                echo $column, str_repeat(' ', $sizes[$index] - $len);
            }
            echo "\n";
        }
    }
}
