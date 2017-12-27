# Pipelines

## Run Bitbucket Pipelines Wherever They Dock

Command line pipeline runner written in PHP, easy to integrate
with TODO Composer both global or on a per project basis.

## Usage

From anywhere within a (Git*) repository or project with a
Bitbucket Pipeline file:

~~~
pipelines
~~~

Runs pipeline commands from [`bitbucket-pipelines.yml`][BBPL]
\[BBPL\].

Memory and time limits are ignored. Press ctrl+c to quit.

The Bitbucket limit of 10 steps per pipeline is ignored.

Exit status is from last pipeline script command, if a command
fails the following script commands and steps are not executed.

The default pipeline is run, if there is no default pipeline in
the file, pipelines tells it and exists with non-zero status.

To execute a different pipeline use the `--pipeline <id>` option
where `id` is one of the list by the `--list` option. Even more
information about the pipelines is available via `--show`. Both
`--list` and `--show` will output and exit.

Run the pipeline as if a tag/branch or bookmark has been pushed
with `--trigger <ref>` where `<ref>` is `tag:<name>`,
`branch:<name>` or `bookmark:<name>`. If there is no according
tags, branches or bookmarks pipeline with that name, the name is
compared against the patterns of the referenced pipelines type
and if found, that pipeline is run. Otherwise the default
pipeline is run, if there is no default pipeline, no pipeline at
all is run and the command exits with non-zero status.

TODO: brace patterns are supported by bitbucket pipelines but not yet by
pipelines.

`--pipeline` and `--trigger` can be used together, `--pipeline`
overrides pipeline from `--trigger` but `--trigger` still
influences the container environment variables.

TODO: get revision from working directory (VCS)

To specify a different file use the  `--basename <basename>`
or `--file <path>` option and/or set the working directory
`--working-dir <path>` in which the file is looked for unless
an absolute path is set by `--file <path>`.

TOOD Use a different project directory `--project-dir <path>` for
the working tree to operate on which otherwise is the working
directory.

By default it operates on the current working tree, TODO(VCS): to
run on a specific revision, reference it (`--revision <ref>`). TODO: The
pipeline is then run against a clean temporary checkout of
the current (Git*) repository or TODO: a copy of the working directory
(`--vcs "none"`; `--working-dir <directory>`; `--project-dir`).
TODO: isolate into a temporary directory and mount it instead,
re-use `--keep` flag on it, offer management of leftover directories
similar to `--docker-list|kill|clean` (session management).

----

\* Mercurial is not yet supported, TODO: Git is used as first VCS,
  if VCS interaction is more clear and/or demand is high, others
  (e.g. Mercurial) should be supported. VCS integration is yet
  on the TODO list.

### Scenarios

Give your project and pipeline changes a quick test run from the
staging area. As pipelines are normally executed far away,
setting them up becomes cumbersome, especially with the guide
given in [Bitbucket Pipelines documentation][BBPL-LOCAL-RUN]
\[BBPL-LOCAL-RUN\].

This is where the `pipelines` command jumps in.

### Get the rubber on the road faster

The `pipelines` command closes the gap between local development
and remote pipeline execution. As long as Docker is installed
and running, the `bitbucket-pipelines.yml` file is parsed and
it is taken care of to execute all steps and their commands
within the container of choice.

Pipelines YAML file parsing, container creation and script
execution is done as closely as possible compared to the
Atlassian Bitbucket Pipeline server. Features include:

* **Dev Mode**: Pipeline from your working tree like never
  before. Pretend to be on any branch, tag or bookmark
  (`--trigger`) even in a different repository or none at all.

  Check if the reference matches a pipeline or just run the
  default (default) or a specific one (`--pipeline`). Use a
  different pipelines file (`--file`) or swap the "repository" by
  changing the working directory (`--working-dir`).

  Containers can be kept for debugging and and manual testing
  of pipelines (use `--keep`). Afterwards manage left overs with
  `--docker-list|kill|clean`. Debugging options to dream for.

* **Container Isolation**: There is one container per step, like
  it is on Bitbucket.

  To isolate the files, use `--deploy` with `copy` to copy the
  files into the container. This isolates file-changes done by
  the pipeline from the working directory which is otherwise by
  default mounted into the container (implicit `--deploy mount`).

* **Ready for Offline**: On the plane? Or just a rainy day on
  a remote location with broken net? Coding while abroad?
  Before going into offline mode, make use of `--images` and the
  shell:

      $ pipelines --images | while read -r image; do \
        docker image pull "$image"; done;

* **Default Image**: The pipelines command uses the default
  image like Bitbucket Pipelines does. Get started out of the
  box, but keep in mind it has roughly 2 GB. TODO: The default
  image can even be overridden (`--default-image`).

* **Pipelines inside Pipeline**: As a special feature and by
  default pipelines mounts the docker socket into each container
  (on systems where the socket is available). That allows to
  launch pipelines from a pipeline as long as the docker client
  is available in the pipeline's container. This feature is not
  available on Bitbucket Pipelines but servers pipelines itself
  well for integration testing on Travis. In combination with
  `--copy mount` (the default), the original working-directory
  gets mounted from the host again.

## Environment

Pipelines mimics all of the [Bitbucket Pipeline in-container
environment variables][BBPL-ENV] \[BBPL-ENV\]:


* `BITBUCKET_BOOKMARK` - conditionally set by `--target`
* `BITBUCKET_BUILD_NUMBER` - always set to '0'
* `BITBUCKET_BRANCH` - conditionally set by `--target`
* `BITBUCKET_CLONE_DIR` - always set to mount point in container
* `BITBUCKET_COMMIT` - faux as no revision triggers a build;
    always set to '0000000000000000000000000000000000000000'
* `BITBUCKET_REPO_OWNER` - current username from
    environment or if not available 'nobody'
* `BITBUCKET_REPO_SLUG` - always set to 'local-has-no-slug'
* `BITBUCKET_TAG` - conditionally set by `--target`
* `CI` - always set to 'true'

All of these (but not `BITBUCKET_CLONE_DIR`) can be set within
the environment pipelines runs and are taken over into container
environment. Example:

    $ BITBUCKET_BUILD_NUMBER=1234 pipelines # build no. 1234

Additionally pipelines sets some environment variables for
introspection:

* `PIPELINES_CONTAINER_NAME` - name of the container itself
* `PIPELINES_ID` - <id> of the pipeline that currently runs
* `PIPELINES_IDS` - list of space separated md5 hashes of so
    far running <id>. used to detect pipelines inside pipelines
    recursion, preventing execution until system failure.
* `PIPELINES_PARENT_CONTAINER_NAME` - name of the container name
    if it was already set when the pipeline started (pipelines
    inside pipeline).

## Details

### Requirements

Pipelines requires a POSIX compatible system supporting the User
Portability Utilities option.

Docker needs to be available locally as `docker` is used to run
pipelines and the working directory is used as volume in the
container (by default unless `--deploy` with `copy` instead of
`mount`). (1)

A recent PHP version is favored, the `pipelines` command needs
it to run. It should work with PHP 5.3+, the phar build requires
PHP 5.4+. A development environment should have PHP 7, this is
especially suggested for future releases.

(1) There is the idea to allow bare-metal execution of pipeline
commands, but this is more specific and can run against pipeline
assumptions (there is no container environment) so is more of
a pipelines file development option (TODO).

### User Tests

Successful use on Ubuntu 16.04 LTS and Mac OS X High Sierra with
PHP and Docker installed.

### Known Bugs

- Version number shows only the revision hash (`--version`) for
  phar builds and a placeholder for the development version;
  should be gone with a tagged release for phar files (TODO:
  version for development version).
- The command ':' in pipelines exec layer is never really exec'ed
  and just has exit status 0 and no standard or error output. It
  is intended for pipelines testing.

### Todo

- Phar build
- Version number
- Docker client inside the container (self-test and other inception fun)
- Braces support for string pattern matching
- Check Docker existence before running
- Docker info command (as seen on Travis)
- Build number option (`--build-number`, default is '0')
- Pass environment file(s) (`--env-file`)
- Stop at manual steps (`--no-manual` to override)
- Run specific steps of a pipeline only
- Better offline mode (`--docker-pull-images`)
- Write limitations section about the file-format support
- Pipeline file properties support
    - clone (1)
    - max-time (1)
    - size (1)
    - custom (1)
    - step.trigger (1)
    - artifacts (1)
    - definitions (1)
- Automatically keep container if step failed, use `--no-fail-keep` to prevent
- use a named volume instead of directly mounting the project directory
  (isolation; create container with volume, docker cp into the path, on FLAG_KEEP
  do not otherwise rm the volume)
- Validation command for bitbucket-pipelines.yml files (so far
  `--show` gives error on parts it runs over and non zero exit
  code)
- Verify steps if a single command to see if it matches an
  executable script file or not.
- Write exit status section about used exit codes

(1) if it is considered that it applies to running local

## References

* \[BBPL]: https://confluence.atlassian.com/bitbucket/configure-bitbucket-pipelines-yml-792298910.html
* \[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
* \[BBPL-LOCAL-RUN]: https://confluence.atlassian.com/bitbucket/debug-your-pipelines-locally-with-docker-838273569.html

[BBPL]: https://confluence.atlassian.com/bitbucket/configure-bitbucket-pipelines-yml-792298910.html
[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
[BBPL-LOCAL-RUN]: https://confluence.atlassian.com/bitbucket/debug-your-pipelines-locally-with-docker-838273569.html
