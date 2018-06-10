<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;

class Help
{
    /**
     * @var Streams
     */
    private $streams;

    /**
     * Help constructor.
     * @param Streams $streams
     */
    public function __construct(Streams $streams)
    {
        $this->streams = $streams;
    }

    public function showVersion()
    {
        $version = Version::resolve(App::VERSION);
        $this->streams->out(
            sprintf("pipelines version %s\n", $version)
        );

        return 0;
    }

    public function showUsage()
    {
        $this->streams->out(
            <<<'EOD'
usage: pipelines [<options>...] [--version | [-h | --help]]
       pipelines [-v | --verbose] [--working-dir <path>]
                 [--[no-|error-]keep] [--prefix <prefix>]
                 [--basename <basename>]
                 [[-e | --env] <variable>] [--env-file <path>]
                 [--no-dot-env-files] [--no-dot-env-dot-dist]
                 [--file <path>] [--dry-run] [--no-run] [--list]
                 [--deploy mount | copy ] [--show] [--images]
                 [--pipeline <id>] [--trigger <ref>] [--verbatim]
       pipelines [-v | --verbose] [--dry-run] [--docker-list]
                 [--docker-kill] [--docker-clean] [--docker-zap]

EOD
        );
    }

    public function showHelp()
    {
        $this->showUsage();
        $this->streams->out(
            <<<'EOD'

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
    --no-dot-env-files    do not pass .env.dist and .env files
                          as environment files to docker
    --no-dot-env-dot-dist dot not pass .env.dist as environment
                          file to docker
    --file <path>         path to the pipelines file, overrides
                          looking up the <basename> file from
                          the current working directory
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

Keep options
    --keep                always keep docker containers
    --error-keep          keep docker docker containers if a
                          step failed; the non-zero exit status
                          is output and the id of the container
                          kept
    --no-keep             do not keep docker containers; default
                          behaviour

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
    --docker-zap          kill and remove all prefixed containers

Less common options
    --debug               flag for trouble-shooting fatal errors
    --prefix <prefix>     use a different prefix for container
                          names, default is 'pipelines'

EOD
        );

        return 0;
    }

    /**
     * @param Args $args
     * @throws InvalidArgumentException
     * @throws StatusException
     */
    public function run(Args $args)
    {
        # quickly handle version
        if ($args->hasOption('version')) {
            StatusException::status($this->showVersion());
        }

        # quickly handle help
        if ($args->hasOption(array('h', 'help'))) {
            StatusException::status($this->showHelp());
        }
    }
}
