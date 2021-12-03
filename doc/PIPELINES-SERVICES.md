# Working with Pipeline Services

The `pipelines` local runner supports services since version 0.0.37 (the
special `docker` service from version 0.0.25 is not covered by
this document, see [*Pipelines inside Pipeline*][pip]).

[pip]: ../README.md#pipelines-inside-pipeline

[Introduction](#introduction-to-services-in-pipelines)
| [Example](#service-example)
| [Validate](#validate-pipeline-services)
| [Trouble-Shoot](#trouble-shoot-starting-service-containers)
| [Debug](#debug-pipeline-services)

## Introduction to Services in Pipelines

A service is another container that is started before the step script
using [host networking] both for the service as well as for the pipeline
step container.

The step script can then access on `localhost` the started service.

After the step script is run (and any optional `after-script`), the
step container is shut down and removed; afterwards any service
containers are shut down and removed.

Services are defined in the `bitbucket-pipelines.yml` file and then
referenced by a pipeline step.

Next to running bitbucket pipelines locally with services, the
`pipelines` runner has options for [validating], [trouble-shooting] and
[debugging services].

[host networking]: https://docs.docker.com/network/host/
[validating]: #validate-pipeline-services
[trouble-shooting]: #trouble-shoot-starting-service-containers
[debugging services]: #debug-pipeline-services

## Service Example

Here a simple service example with a `redis` service in a step
script that pings it.

### Defining a Service

A service definition in `bitbucket-pipelines.yml` is required to make
use of the service in a pipeline step:

```yaml
definitions:
  services:
    redis:
      image: redis:6.0.4-alpine
```
*Pipelines YAML fragment:* Definition of the `redis` service

The service named `redis` is then defined and ready to use by the step
`services`.

### Use a Service in a Pipeline Step

As now defined, the step is ready to use by the steps' `services` list
by referencing the defined service *name*, here `redis`.

A *default* pipeline named *redis service example* as an example:

```yaml
pipelines:
  default:
    - step:
        name: redis service example
        image: redis:alpine
        script:
          - redis-cli -h localhost ping
        services:
          - redis
```
*Pipelines YAML fragment:* Default pipeline step with the `redis`
service

Note the `services` list at the very end, it has the `redis` entry. This
tells `pipelines` to start it for the step.

### Run the Pipeline with the Example Redis Service

That is it for the configuration, let us run it:

```
$ pipelines
+++ step #1

    name...........: "redis service example"
    effective-image: redis:alpine
    container......: pipelines-1.redis-service-example.default.pipelines
    container-id...: 1fd11cdf4291

+++ copying files into container...


+ redis-cli -h localhost ping
PONG
```
*Command line example:* Running the default pipeline with the `redis`
service successfully pinging the service.

## Validate Pipeline Services

To verify the services are defined and properly wired to pipeline steps,
use the `--show-services` switch, it specifically shows the services by
step and checks if services are defined:

```
$ pipelines --show-services
PIPELINE ID    STEP    SERVICE    IMAGE
default        1       redis      redis:6.0.4-alpine
```
*Command line example:* Default pipeline first step uses the `redis`
service defined with the *`redis:6.0.4-alpine`* image.

In case of an error, e.g. the service definition is missing or variables
are not well-defined, this would be shown:

```
PIPELINE ID    STEP    SERVICE    IMAGE
default        1       ERROR      Undefined service: "redis"
```
```
PIPELINE ID    STEP    SERVICE    IMAGE
                       ERROR      variable MYSQL_RANDOM_ROOT_PASSWORD \
                                  should be a string (it is currently \
                                  defined as a boolean)
```
*Command line examples:* Services validation errors with `--show-services`

The `--show-services` option exits with zero status or non-zero in case
an error was found.

Part of this service information is also available with the `--show`
command, errors in the file are highlighted more prominently thought:

```
$ pipelines --show
pipelines: file parse error: variable MYSQL_RANDOM_ROOT_PASSWORD \
should be a string (it is currently defined as a boolean)
```
*Command line example:* Bogus variable with `--show`

Without any errors the `--show` option displays the information and
exits:

```
$ pipelines --show
PIPELINE ID    STEP    IMAGE                 NAME
default        1       redis:alpine          "redis service example"
default        1       redis:6.0.4-alpine    service:redis
```
*Command line example:* Pipelines shows services for each pipeline step

## Trouble-Shoot Starting Service Containers

Sometimes service containers do not start properly, the service
container exits prematurely or other unintended things are happening
setting up a service.

It is possible to start a pipelines service container manually to review
the start sequence.

### The `--service <service>` option

To start any defined service use the `--service` option with the
name of the service in the `definitions` section.

```
$ pipelines --service mysql
error: database is uninitialized and password option is not specified
  You need to specify one of MYSQL_ROOT_PASSWORD, \
  MYSQL_ALLOW_EMPTY_PASSWORD and MYSQL_RANDOM_ROOT_PASSWORD
```
*Command-line example:* Missing variable for pipelines `mysql` service

Fixing the service definition (here by adding a variable to it) and
running the `pipelines --service mysql` again, will show the service
properly running by displaying the output of the service.

Press <kbd>ctrl</kbd> + <kbd>z</kbd> to suspend the process and
either `$ bg` to send the service in the background or `$ kill %`
which will shut down the service container.

> **Note:** If the `--keep` or `--error-keep` option has been used to
run the pipeline and the service exited in error, the stopped
service container needs to be removed before `--service` can
successfully run the service:
>
>    ```
>    $ docker rm pipelines-service-mysql.pipelines
>    pipelines-service-mysql.pipelines
>    ```

## Debug Pipeline Services

As the `pipelines` utility is designed to run bitbucket pipelines
locally, trouble-shooting and debugging pipeline services is easily
possible and supported with various options re-iterating quickly
locally.

### Keep Service Containers with `--keep`

To leave service containers on the system for inspection and re-
iteration, use the `--keep` (or `--error-keep`) option. The step
container and all service containers are then kept and not removed:

```
$ pipelines --keep
+++ step #1

    name...........: "redis service example"
    effective-image: redis:alpine
[...]
+ redis-cli -h localhost ping
PONG
keeping container id 0aa93bcf3b7b
keeping service container pipelines-service-redis.pipelines
```
*Command line example:* Keeping service containers for inspection

### Inspect Kept Containers with Docker

With the containers still running, service configuration problems can
be reviewed, e.g. in case a service didn't start well. For example
by inspecting the logs:

```
$ docker logs pipelines-service-redis.pipelines
1:C 01 Jun 2020 08:42:42.739 # oO0OoO0OoO0Oo Redis is starting oO0OoO0OoO0Oo
1:C 01 Jun 2020 08:42:42.739 # Redis version=6.0.4, bits=64, commit=00000000, modified=0, pid=1, just started
1:C 01 Jun 2020 08:42:42.739 # Warning: no config file specified, using the default config. In order to specify a config file use redis-server /path/to/redis.conf
1:M 01 Jun 2020 08:42:42.740 * Running mode=standalone, port=6379.
1:M 01 Jun 2020 08:42:42.740 # WARNING: The TCP backlog setting of 511 cannot be enforced because /proc/sys/net/core/somaxconn is set to the lower value of 128.
1:M 01 Jun 2020 08:42:42.740 # Server initialized
1:M 01 Jun 2020 08:42:42.740 # WARNING overcommit_memory is set to 0! Background save may fail under low memory condition. To fix this issue add 'vm.overcommit_memory = 1' to /etc/sysctl.conf and then reboot or run the command 'sysctl vm.overcommit_memory=1' for this to take effect.
1:M 01 Jun 2020 08:42:42.740 # WARNING you have Transparent Huge Pages (THP) support enabled in your kernel. This will create latency and memory usage issues with Redis. To fix this issue run the command 'echo never > /sys/kernel/mm/transparent_hugepage/enabled' as root, and add it to your /etc/rc.local in order to retain the setting after a reboot. Redis must be restarted after THP is disabled.
1:M 01 Jun 2020 08:42:42.740 * Ready to accept connections
```
*Command line example:* Reviewing logs of a pipeline service

### Clean with `--docker-zap`

Running the pipeline again will re-use the existing step and service containers
for a fast re-iteration. In case this is unwanted and a fresh run is preferred,
just "zap" all kept pipeline containers:

```
$ pipelines --docker-zap
```
*Command line example:* Remove all pipeline containers incl. service containers

Afterwards *all* pipelines containers are gone and will be re-created on next
pipelines run.
