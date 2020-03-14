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
usage: pipelines [<options>] --version | -h | --help
       pipelines [<options>] [--working-dir <path>] [--file <path>]
                 [--basename <basename>] [--prefix <prefix>] [--verbatim]
                 [--[no-|error-]keep] [--no-run]
                 [(-e | --env) <variable>] [--env-file <path>]
                 [--no-dot-env-files] [--no-dot-env-dot-dist]
                 [--docker-client <package>]
                 [--deploy mount | copy ] [--pipeline <id>]
                 [(--step | --steps) <steps>] [--no-manual]
                 [--trigger <ref>]
       pipelines [<options>] --list | --show | --images
       pipelines [<options>] --docker-client-pkgs
       pipelines [<options>] [--docker-list] [--docker-kill]
                 [--docker-clean] [--docker-zap]

EOD
        );
    }

    public function showHelp()
    {
        $this->showUsage();
        $this->streams->out(
            <<<'EOD'

Generic options
    -h, --help            show usage and help information
    --version             show version information
    -v, --verbose         be more verbose, show more information and
                          commands to be executed
    --dry-run             do not execute commands, e.g. invoke docker or
                          run containers, with --verbose show the commands
                          that would have run w/o --dry-run

Pipeline runner options
    --basename <basename> set basename for pipelines file, defaults to
                          'bitbucket-pipelines.yml'
    --deploy mount|copy   how files from the working directory are placed
                          into the pipeline container:
                          copy     (default) working dir is copied into
                                 the container. stronger isolation as the
                                 pipeline scripts can change all files
                                 without side-effects in the working
                                 directory
                          mount    the working directory is mounted.
                                 fastest, no isolation
    --file <path>         path to the pipelines file, overrides looking up
                          the <basename> file from the current working
                          directory
    --trigger <ref>       build trigger, <ref> can be of either
                          tag:<name>, branch:<name>, bookmark:<name> or
                          pr:<branch-name>
                          determines the pipeline to run
    --pipeline <id>       run pipeline with <id>, use --list for a list of
                          all pipeline ids available.
    --step, --steps <steps>
                          execute not all but this/these <steps>. duplicates
                          and different order allowed, <steps> are a comma/
                          space separated list of step and step ranges, e.g.
                          1 2 3; 1-3; 1,2-3; 3-1 or -1,3- and 1,1,2,2,3,3.
    --no-manual           ignore manual steps, by default manual steps stop
                          the pipeline execution when not the first step
                          in the pipeline invocation
    --verbatim            only give verbatim output of the pipeline, do not
                          display other information like which step currently
                          executes, which image is in use etc.
    --working-dir <path>  run as if pipelines was started in <path>
    --no-run              do not run the pipeline
    --prefix <prefix>     use a different prefix for container
                          names, default is 'pipelines'

Environment control options
    -e, --env <variable>  pass or set an environment <variable> for the
                          docker container, just like docker run, the
                          variable can be the name of a variable which
                          adds the variable to the container if exported
                          or a variable definition with the name of the
                          variable, the equal sign "=" and the value,
                          e.g. --env NAME=value
    --env-file <path>     pass variables from environment file to the
                          docker container
    --no-dot-env-files    do not pass .env.dist and .env files as
                          environment files to docker
    --no-dot-env-dot-dist dot not pass .env.dist as environment file to
                          docker

Keep options
    --keep                always keep docker containers
    --error-keep          keep docker docker containers if a step failed;
                          outputs the non-zero exit status and the id of
                          the container kept and exit w/ container exec
                          exit status
    --no-keep             do not keep docker containers; default behaviour

Docker service options
    --docker-client <package>
                          which docker client binary to use for the
                          pipeline service 'docker'
                          defaults to 'docker-19.03.1-linux-static-x86_64'
                          package
    --docker-client-pkgs  list all docker client packages that ship with
                          pipelines and exit

File information options
    --images              list all images in file, in order of use, w/o
                          duplicate names and exit
    --list                list pipeline <id>s in file and exit
    --show                show information about pipelines in file and
                          exit

Docker container maintenance options
      usage might leave containers on the system. either by interrupting
      a running pipeline step or by keeping the running containers
      (--keep, --error-keep)

      pipelines uses a prefix followed by '-' and a compound name based
      on step-number, step-name, pipeline id and image name for container
      names. the prefix is either 'pipelines' or the one set by
      --prefix <prefix>

      three options are built-in to monitor and interact with leftovers,
      if one or more of these are given, the following operations are
      executed in the order from top to down:

    --docker-list         list prefixed containers
    --docker-kill         kills prefixed containers
    --docker-clean        remove (non-running) containers with
                          pipelines prefix

    --docker-zap          kill and remove all prefixed containers in one
                          go

Less common options
    --debug               flag for trouble-shooting fatal errors, errors,
                          warnings and notices

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
