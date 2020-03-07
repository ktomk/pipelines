# Pipelines

## Run Bitbucket Pipelines Wherever They Dock

[![Build Status](https://travis-ci.org/ktomk/pipelines.svg?branch=master)](https://travis-ci.org/ktomk/pipelines)
[![Code Coverage](https://scrutinizer-ci.com/g/ktomk/pipelines/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ktomk/pipelines/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ktomk/pipelines/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ktomk/pipelines/?branch=master)

Command line pipeline runner written in PHP. Available from
Github or Packagist.

[Usage](#usage) | [Environment](#environment) |
[Exit Status](#exit-status) | [Details](#details) |
[References](#references)

## Usage

From anywhere within a project or (Git) repository with a
Bitbucket Pipeline file:

~~~
pipelines
~~~

Runs pipeline commands from [`bitbucket-pipelines.yml`][BBPL]
\[BBPL].

Memory and time limits are ignored. Press ctrl+c to quit.

The Bitbucket limit of 100 (previously 10) steps per pipeline
is ignored. Use `--steps <steps>` to specify which step(s) to
execute in which order.

Exit status is from last pipeline script command, if a command
fails the following script commands and steps are not executed.

The default pipeline is run, if there is no default pipeline in
the file, pipelines tells it and exists with non-zero status.

To execute a different pipeline use the `--pipeline <id>` option
where `<id>` is one of the list by the `--list` option. Even more
information about the pipelines is available via `--show`. Both
`--list` and `--show` will output and exit.

Run the pipeline as if a tag/branch or bookmark has been pushed
with `--trigger <ref>` where `<ref>` is `tag:<name>`,
`branch:<name>`, `bookmark:<name>` or `pr:<branch-name>`. If
there is no tag, branch, bookmark or pull-request pipeline with
that name, the name is compared against the patterns of the
referenced type and if found, that pipeline is run.

Otherwise the default pipeline is run, if there is no default
pipeline, no pipeline at all is run and the command exits with
non-zero status.

`--pipeline` and `--trigger` can be used together, `--pipeline`
overrides pipeline from `--trigger` but `--trigger` still
influences the container environment variables.

To specify a different file use the  `--basename <basename>`
or `--file <path>` option and/or set the working directory
`--working-dir <path>` in which the file is looked for unless
an absolute path is set by `--file <path>`.

By default it operates on the current working tree which is
copied into the container to isolate running the pipeline from
the working directory by default (implicit `--deploy copy`).

Alternatively the working directory can be mounted into the
pipelines container by using `--deploy mount`.

Use `--keep` flag to keep containers after the pipeline has
finished for further inspection. By default all containers are
destroyed. Sometimes for development it is interesting to keep
containers on error only, the `--error-keep` flag is for that.

Manage leftover containers with `--docker-list` showing all
pipeline containers, `--docker-kill` to kill running containers
and `--docker-clean` to remove stopped pipeline containers. Use
in combination to fully clean, e.g.:

    $ pipelines --docker-list --docker-kill --docker-clean

Or just run for a more shy clean-up:

    $ pipelines --docker-zap

to kill and remove all pipeline containers (w/o showing a list)
first.

Validate your bitbucket-pipelines.yml file with `--show` which
highlights errors found.

Inspect your pipeline with `--dry-run` which will process the
pipeline but not execute anything. Combine with `--verbose` to
show the commands which would have run verbatim.

Use `--no-run` to not run the pipeline at all, this can be used
to test the utilities options.

Pipeline environment variables can be passed/exported to or set
for your pipeline by name or file with `-e`, `--env` and
`--env-file` options.

Environment variables are also loaded from dot env files named
`.env.dist` and `.env` and processed in that order before the
environment options. Use of `--no-dot-env-files` prevents such
automatic loading, `--no-dot-env-dot-dist` for the .env.dist
file only.

More information on pipelines environment variables in the
[*environment* section](#environment) below.

A full display of the pipelines utility options and arguments is
available via `-h`, `--help`.

### Usage Scenario

Give your project and pipeline changes a quick test run from the
staging area. As pipelines are normally executed far away,
setting them up becomes cumbersome, the guide given in [Bitbucket
Pipelines documentation][BBPL-LOCAL-RUN] \[BBPL-LOCAL-RUN] has some
hints and is of help, but it is not about a bitbucket pipelines runner.

This is where the `pipelines` command jumps in.

The `pipelines` command closes the gap between local development
and remote pipeline execution by executing any pipeline
configured on your local development box. As long as Docker is
accessible locally, the `bitbucket-pipelines.yml` file is parsed
and it is taken care of to execute all steps and their commands
within the container of choice.

Pipelines YAML file parsing, container creation and script
execution is done as closely as possible compared to the
Atlassian Bitbucket Pipeline service. Environment variables can
be passed into each pipeline as needed. You can even switch to a
different CI/CD service like Github/Travis with little
integration work fostering your agility and vendor independence.

Features include:

* **Dev Mode**: Pipeline from your working tree like never
  before. Pretend to be on any branch, tag or bookmark
  (`--trigger`) even in a different repository or none at all.

  Check if the reference matches a pipeline or just run the
  default (default) or a specific one (`--pipeline`). Use a
  different pipelines file (`--file`) or swap the "repository" by
  changing the working directory (`--working-dir <path>`).

  If a pipeline step fails, the steps container can be kept for
  further inspection on error with the `--error-keep` option.
  The container id is shown which makes it easy to spawn a shell
  inside:

      $ docker exec -it $ID /bin/sh

  Containers can be always kept for debugging and manual testing
  of a pipeline with `--keep` and with the said `--error-keep` on
  error only.

  Afterwards manage left overs with `--docker-list|kill|clean` or
  clean up with `--docker-zap`.

  Debugging options to dream for.

* **Container Isolation**: There is one container per step, like
  it is on Bitbucket.

  The files are isolated by being copied into the container
  before the pipeline step script is executed (implicit
  `--deploy copy`).

  Alternatively files can be mounted into the container instead
  with `--deploy mount` which normally is faster, but the working
  tree might become changed by the container script which can be
  a problem when Docker runs system-wide as containers do not isolate
  users (e.g. root is root).  \
  Better with `--deploy mount` is using
  Docker in rootless mode where files manipulated in the container /
  pipeline are accessible to the own user account (like root is user).

  * Further reading: [*How-To Rootless Pipelines*](doc/PIPELINES-HOWTO-ROOTLESS.md)

* **Pipeline Integration**: Export files from the pipeline by
  making use of artifacts, these are copied back into the working
  tree while in (implicit) `--deploy copy` mode. Artifacts files
  are always created by the user running pipelines. This also
  (near) perfectly emulates the file format `artifacts` section
  with the benefit/downside that you might want to prepare a
  clean build in a step script while you can keep artifacts from
  pipelines locally.

* **Ready for Offline**: On the plane? Riding Deutsche Bahn? Or
  just a rainy day on a remote location with broken net? Coding
  while abroad? Or just Bitbucket down again? Before going into
  offline mode, make use of `--images` and the shell:

      $ pipelines --images | while read -r image; do \
        docker image pull "$image"; done;

* **Default Image**: The pipelines command uses the default
  image like Bitbucket Pipelines does. Get started out of the
  box, but keep in mind it has roughly 2 GB
  ("`atlassian/default-image:latest`").

* **Pipelines inside Pipeline**: As a special feature and by
default pipelines mounts the docker socket into each container (on
  systems where the socket is available).  \
  That allows to launch pipelines from a pipeline as long as the Docker
  client is available in the pipeline's container. Pipelines will take
  care to have the Docker client as `/usr/bin/docker` when the pipeline
  has the `docker` service (`services: \n - docker`).  \
  This feature is similar to [run Docker commands in Bitbucket
  Pipelines][BBPL-DCK] \[BBPL-DCK].

  The pipelines inside pipeline feature serves pipelines itself
  well for integration testing on Travis. In combination with
  `--deploy mount`, the original working-directory gets mounted
  from the host again. Additional protection against endless
  loops by recursion is implemented to prevent accidental
  endless loops of pipelines inside pipeline invocations.

  * Further reading: [*How-To Docker Client Binary Packages for
    Pipelines*](doc/PIPELINES-HOWTO-DOCKER-CLIENT-BINARY.md)

## Environment

Pipelines mimics "all" of the [Bitbucket Pipeline in-container
environment variables][BBPL-ENV] \[BBPL-ENV], also known as
environment parameters:

* `BITBUCKET_BOOKMARK` - conditionally set by `--trigger`
* `BITBUCKET_BUILD_NUMBER` - always set to "`0`"
* `BITBUCKET_BRANCH` - conditionally set by `--trigger`
* `BITBUCKET_CLONE_DIR` - always set to deploy point in container
* `BITBUCKET_COMMIT` - faux as no revision triggers a build;
    always set to "`0000000000000000000000000000000000000000`"
* `BITBUCKET_REPO_OWNER` - current username from environment or
    if not available "`nobody`"
* `BITBUCKET_REPO_SLUG` - base name of project directory
* `BITBUCKET_TAG` - conditionally set by `--trigger`
* `CI` - always set to "`true`"

All of these (but not `BITBUCKET_CLONE_DIR`) can be set within
the environment pipelines runs in and are taken over into container
environment. Example:

    $ BITBUCKET_BUILD_NUMBER=123 pipelines # build no. 123

More information on (Bitbucket) pipelines environment variables
can be found in the [*Pipelines Environment Variable Usage
Reference*](./doc/PIPELINES-VARIABLE-REFERENCE.md).

Additionally pipelines sets some environment variables for
introspection:

* `PIPELINES_CONTAINER_NAME` - name of the container itself
* `PIPELINES_ID` - `<id>` of the pipeline that currently runs
* `PIPELINES_IDS` - list of space separated md5 hashes of so
    far running `<id>`s. used to detect pipelines inside pipeline
    recursion, preventing execution until system failure.
* `PIPELINES_PARENT_CONTAINER_NAME` - name of the container name
    if it was already set when the pipeline started (pipelines
    inside pipeline "pip").
* `PIPELINES_PIP_CONTAINER_NAME` - name of the first (initial) pipeline
    container. Used by pipelines inside pipelines ("pip").
* `PIPELINES_PROJECT_PATH` - path of the original project as if
    it would be used for `--deploy`  with `copy` or `mount` so
    that it is possible inside a pipeline to do `--deploy mount`
    when the current container did not mount. A mount always
    requires the path of the project directory on the system
    running pipelines. With no existing mount (e.g. `--deploy
    copy`) it would otherwise be unknown. Manipulating this parameter
    within a pipeline leads to undefined behaviour and can have
    system security implications.

These environment variables are managed by pipelines itself. Some of
them can be injected which can lead to undefined behaviour and can have
system security implications.

Next to these special purpose environment variables, any other
environment variable can be imported into or set in the container
via the `-e`, `--env` and `--env-file` options. These behave
exactly as documented for the [`docker run` command][DCK-RN]
\[DCK-RN].

Instead of specifying custom environment parameters for each
invocation, pipelines by default automatically uses the `.env.dist`
and `.env` files from each project supporting the same file-format
for environment variables as docker.

## Exit Status

Exit status on success is 0 (zero).

A non zero exit status denotes an error:

- 1  : An argument supplied (also a missing one) caused the error.
- 2  : An error is caused by the system not being able to fulfill
       the command (e.g. a file can not be read).
- 127: Running pipelines inside pipelines failed due to detecting
       an endless loop.

### Example

Not finding a file might cause exit status 2 (two) on error
because a file is not found, however with a switch like `--show`
the exit status might still be 1 (one) as there was an error
showing that the file does not exists (indirectly) and the error
is more prominently showing all pipelines of that file.

## Details

[Requirements](#requirements) | [User Tests](#user-tests) |
[Installation](#installation) | [Known Bugs](#known-bugs) |
[Todo](#todo)

### Requirements

Pipelines works best on a POSIX compatible system having a PHP
runtime.

Docker needs to be available locally as `docker` command as it is
used to run the pipelines. Rootless Docker is supported.

A recent PHP version is favored, the `pipelines` command needs
it to run. It should work with PHP 5.3+, the phar build requires
PHP 5.4+. A development environment should have PHP 7, this is
especially suggested for future releases.

Installing the [PHP YAML extension][PHP-YAML] \[PHP-YAML] is
highly recommended as it does greatly improve parsing the
pipelines file which is otherwise with a YAML parser on it's
own.

### User Tests

Successful use on Ubuntu 16.04 LTS, Ubuntu 18.04 LTS and Mac OS
X Sierra and High Sierra with PHP and Docker installed.

### Known Bugs

- The command "`:`" in pipelines exec layer is never really
  executed but emulated having exit status 0 and no standard or
  error output. It is intended for pipelines testing.

- Brace expansion (used for glob patterns with braces) is known
  to fail in some cases. This *could* affect matching pipelines,
  collecting asset paths and *did* affect building the phar file.  \
  For the first two, this has never been reported or experienced,
  for building the phar file the workaround was to entail the
  larger parts of the pattern.

- The libyaml based parser does not support dots (".") in anchor
  names.

### Installation

[Phar (Download)](#download-the-phar-php-archive-file) |
[Composer](#install-with-composer) |
[Phive](#install-with-phive) |
[Source (also w/ Phar)](#install-from-source) |
[Full Project (Development)](#install-full-project-for-development)

Installation is available by downloading the phar archive from
Github, via Composer/Packagist or with Phive and it should always
work from source which includes building the phar file.

#### Download the PHAR (PHP Archive) File

Downloads are available on Github. To obtain the latest released
version, use the following URL:

    https://github.com/ktomk/pipelines/releases/latest/download/pipelines.phar

Rename the phar file to just "`pipelines`", set the executable
bit and move it into a directory where executables are found.

Downloads from Github are available since version 0.0.4. All
releases are listed on the website:

    https://github.com/ktomk/pipelines/releases

#### Install with Composer

Suggested is to install it globally (and to have the global
composer vendor/bin in PATH) so that it can be called with ease
and there are no dependencies in a local project:

    $ composer global require ktomk/pipelines

This will automatically install the latest available version.
Verify the installation by invoking pipelines and output the
version:

    $ pipelines --version
    pipelines version 0.0.19

To uninstall remove the package:

    $ composer global remove ktomk/pipelines

Take a look at [Composer from getcomposer.org][COMPOSER]
\[COMPOSER], a *Dependency Manager for PHP*. Pipelines has
support for composer based installations, which might include
upstream patches.

#### Install with Phive

Perhaps the most easy way to install when *phive* is available:

    $ phive install pipelines

Even if your PHP version does not have the Yaml extension this
should work out of the box. If you use *composer* and you're a
PHP aficionado, dig into *phive* for your systems and workflow.

Take a look at [Phive from Phar.io][PHARIO] \[PHARIO], the *PHAR
Installation and Verification Environment (PHIVE)*. Pipelines has
full support for phar.io/phar based installations which includes
support for the **phive** utility including upstream patches.

#### Install from Source

To install from source, checkout the source repository and
symlink the executable file `bin/pipelines` into a segment of
PATH, e.g. your HOME/bin directory or similar. Verify the
installation by invoking pipelines and output the version:

    $ pipelines --version
    pipelines version 0.0.19 # NOTE: the version is exemplary

To create a phar archive from sources, invoke from within the
projects root directory the build script:

    $ composer build
    building 0.0.19-1-gbba5a43 ...
    pipelines version 0.0.19-1-gbba5a43
    file.....: build/pipelines.phar
    size.....: 240 191 bytes
    SHA-1....: 9F118A276FC755C21EA548A77A9DBAF769B93524
    SHA-256..: 0C38CBBB12E10E80F37ECA5C4C335BF87111AC8E8D0490D38683BB3DA7E82DEF
    file.....: 1.1.0
    api......: 1.1.1
    extension: 2.0.2
    php......: 7.2.16-[...]
    uname....: [...]
    count....: 62 file(s)
    signature: SHA-1 E638E7B56FAAD7171AE9838DF6074714630BD486

The phar archive then is (as written in the output of the build):

    build/pipelines.phar

Check the version by invoking it:

    $ build/pipelines.phar --version
    pipelines version 0.0.19-1-gbba5a43

### Install Full Project For Development

When working with git clone, clone the repository
and then invoke `composer install`. The project
is then setup for development.

Alternatively it's possible to do the same via
composer directly:

~~~
$ composer create-project --prefer-source --keep-vcs ktomk/pipelines
~~~

Follow the instructions in [*Install from Source*](#install-from-source)
to use the development version.

### Todo

- [x] Support for private Docker repositories
- [x] Inject docker client if docker service is enabled
- [ ] Support BITBUCKET_PR_DESTINATION_BRANCH with
      `--trigger pr:<source>:<destination>`
- [ ] Option to not mount docker.sock
- [ ] Run specific steps of a pipeline (only) to put the user
      back into command on errors w/o re-running everything
- [ ] Run pipelines as current user (`--deploy mount` should
      not enforce the container default user \[often "root"]
      for project file operation), however the Docker utility
      still requires you (the current user) to be root like, so
      technically there is little win (see [Rootless
      Pipelines](doc/PIPELINES-HOWTO-ROOTLESS.md) for what works
      better in this regard already)
- [ ] More accessible offline preparation (e.g.
      `--docker-pull-images`, `--go-offline`)
- [ ] Copy local composer cache into container for better
      (offline) usage in PHP projects
- [ ] Check Docker existence before running a pipeline
- [ ] Stop at manual steps (`--no-manual` to override)
- [ ] Pipes support
- [ ] Write section about the file format support/limitations
- [ ] Pipeline file properties support
    - clone (*1)
    - max-time (*1)
    - size (*1)
    - step.trigger (*1)
    - definitions (*1)
  (*1) if it is considered that it applies to running local
- [ ] Get VCS revision from working directory
- [ ] Use a different project directory `--project-dir <path>` to
  specify the root path to deploy into the container, which
  currently is the working directory (`--working-dir <path>`)
- [ ] Run on a specific revision, reference it (`--revision <ref>`);
  needs a clean VCS checkout in a temporary folder which then
  should be copied into the container
- [ ] Override the default image name (`--default-image <name>`)

## References

* \[BBPL]: https://confluence.atlassian.com/bitbucket/configure-bitbucket-pipelines-yml-792298910.html
* \[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
* \[BBPL-LOCAL-RUN]: https://confluence.atlassian.com/bitbucket/debug-your-pipelines-locally-with-docker-838273569.html
* \[BBPL-DCK]: https://confluence.atlassian.com/bitbucket/run-docker-commands-in-bitbucket-pipelines-879254331.html
* \[COMPOSER]: https://getcomposer.org/
* \[DCK-RN]: https://docs.docker.com/engine/reference/commandline/run/
* \[PHARIO]: https://phar.io/
* \[PHP-YAML]: https://pecl.php.net/package/yaml

[BBPL]: https://confluence.atlassian.com/bitbucket/configure-bitbucket-pipelines-yml-792298910.html
[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
[BBPL-LOCAL-RUN]: https://confluence.atlassian.com/bitbucket/debug-your-pipelines-locally-with-docker-838273569.html
[BBPL-DCK]: https://confluence.atlassian.com/bitbucket/run-docker-commands-in-bitbucket-pipelines-879254331.html
[COMPOSER]: https://getcomposer.org/
[DCK-RN]: https://docs.docker.com/engine/reference/commandline/run/
[PHARIO]: https://phar.io/
[PHP-YAML]: https://pecl.php.net/package/yaml
