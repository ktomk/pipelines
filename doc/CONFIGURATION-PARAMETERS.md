# Configuration Parameters

Currently there are some configuration parameters for `pipelines`.

They can be set with `-c <name>=<value>` and are set to their default
values. There is yet no configuration file or similar.

## `docker.client.path`

Path of the docker client binary within the pipeline step container.

* Default: `/usr/bin/docker`
* Related option: `--docker-client`

## `docker.socket.path`

Path to docker socket file, mount point within the container to mount
the docker socket and as well to check for the docker socket on the
host and within mount binds.

* Default: `/var/run/docker.sock`
* Related environment parameter: `DOCKER_HOST` (See [*Environment
  variables* in *Use the Docker command line*][DCK_HOST])
* Related: [*How-To Rootless Pipelines*](PIPELINES-HOWTO-ROOTLESS.md)

[DCK_HOST]: https://docs.docker.com/engine/reference/commandline/cli/#environment-variables

## `script.bash-runner`

Conditionally run scripts with `/bin/bash` instead of `script.runner` (`/bin/sh`
by default) if `/bin/bash` is a standard file and executable in the container.

Setting `script.bash-runner` to `false` prevents this check.

* Default: `true` (bool)
* Related configuration parameter: [`script.runner`](#scriptrunner)

## `script.exit-early`

Executing the step and after scripts use `set -e` to exit
early.

To eliminate side-effects, a more strict check after each script
command can be enabled by setting the parameter to true with the effect
that `set -e` / `set +e` is ineffective and the step script stops
after *any* shell pipe with a non-zero exit status.

* Default: `false` (bool).

## `script.runner`

The default script runner (`/bin/sh`), step scripts are run with this command.

The step script is passed to the runner via standard input. The runner must be
executable in the container.

When `script.bash-runner` is `true` (default), the `script.runner` may be
overridden by `/bin/bash` if found in the container. This behaviour can be
disabled by setting `script.bash-runner` is `false`.

* Default: `/bin/sh`
* Related configuration parameter: [`script.bash-runner`](#scriptbash-runner)
* Related option: `--step-script`

## `step.clone-path`

Mount point / destination of the project files within a pipeline step
container.

Is the absolute path inside the container and must start with a slash
(`/`), multiple directory components are supported, none of them must
be relative (no `.` or `..` segments allowed). The path must not end
with a slash.

* Default: `/app`
* Related environment parameter: `BITBUCKET_CLONE_DIR` (Compare
[*Pipelines Environment Variable Usage*](PIPELINES-VARIABLE-REFERENCE.md))
