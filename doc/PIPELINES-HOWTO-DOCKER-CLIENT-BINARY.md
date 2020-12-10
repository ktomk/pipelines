# How-To Docker Client Binary Packages for Pipelines

## Introduction

Pipelines ships with some hard-encoded packages for a smaller number
of different docker client versions including the exact same one at the
time of writing that is used by Atlassian Bitbucket Cloud Pipelines
Plugin.

These packages are meta-data information on how to obtain a static
binary of the Docker client and allow to use a static Docker client
binary in a reproducible manner.

Static Docker client binaries are useful for `pipelines` as they can be
mounted into nearly any Linux container without further installation
requirements (next to provide connectivity to the Docker daemon).

This document is about how-to obtain the static docker client binary and
all the meta-data to make use of it and also on how to create such
packages next to existing packages or for improving pipelines with new
package definitions.

Be it a local `.yml` package file, a package that ships with the
pipelines utility or a custom build static docker client binary.

While doing so, this document also explains many details about the file-
format of `.yml` docker client binary packages and the `--docker-client`
option and arguments as well as the `--docker-client-pkgs` option.

These options are generally useful to use a different than the default
docker client.

## Table of Contents

* [List all Available Docker Static Binary Packages](#list-all-available-docker-static-binary-packages)
* [Create Docker Client Static Binary Packages for Pipelines](#create-docker-client-static-binary-packages-for-pipelines)
    * [Find the Binary and Download the Release Package](#find-the-binary-and-download-the-release-package)
    * [Obtain Meta-Information](#obtain-meta-information)
    * [Encode Meta-Information into a YAML File](#encode-meta-information-into-a-yaml-file)
    * [Add Packages to Pipelines Project](#add-packages-to-pipelines-project)
* [Last but not Least: Use a Custom Build Static Docker Client Binary](#last-but-not-least-use-a-custom-build-static-docker-client-binary)

## List all Available Docker Static Binary Packages

To display a list of all available docker client versions use the
`--docker-client-pkgs` option:

```bash
$ bin/pipelines --docker-client-pkgs
docker-17.12.0-ce-linux-static-x86_64
docker-18.09.1-linux-static-x86_64
docker-19.03.1-linux-static-x86_64
docker-42.42.1-binsh-test-stub # only for development version
```

It shows the list of the built-in packages which can be used right away
as an option to the `--docker-client` argument.

This list does not highlight the default one, so here a short
description about each of these:

* `docker-17.12.0-ce-linux-static-x86_64`: this version was in use when
  using the [`docker-client-install.sh`][dci1] script inside a pipeline.
* `docker-18.09.1-linux-static-x86_64`: this version was found in use
  by the Atlassian Bitbucket Cloud Pipelines Plugin:
  ```bash
  $ docker --version
  Docker version 18.09.1, build 4c52b90
  ```
* `docker-19.03.1-linux-static-x86_64`: the version used mainly to
  develop pipelines, in specific the Docker Service feature, which was
  sort of current while doing the development work. It includes fixes in
  environment variable handling (18.07.0 / [docker/cli#1019]) which
  prevented pipelines to properly import environment variables from the
  users' environment by environment variable files. This became
  prominent back in April 2018.
* `docker-42.42.1-binsh-test-stub`: a fake package used for testing that
  is not a Docker client at all. When the pipelines project is cloned,
  this is the only package that works offline out of the box and is
  only available after `composer install` has been run.

[dci1]: ../lib/pipelines/docker-client-install.sh
[docker/cli#1019]: https://github.com/docker/cli/pull/1019

## Create Docker Client Static Binary Packages for Pipelines

It might not be always in your case that pipelines ships with the
package of your wish.

### Find the binary and download the release package


Docker binaries are available for different platforms from the
`download.docker.com` website:

* https://download.docker.com/linux/static/stable/x86_64/

Browse for the version that you need to work in a pipeline container, it
might be necessary to change the architecture, fiddle with the path
component of the URL above.

In writing this how-to the docker-17.12.0-ce.tgz for Linux x86_64 has
been created exemplary (it is now part of pipelines):

* https://download.docker.com/linux/static/stable/x86_64/docker-17.12.0-ce.tgz

To download a utility like `wget` or similar can be used:

```bash
$ wget https://download.docker.com/linux/static/stable/x86_64/docker-17.12.0-ce.tgz
--2019-12-15 17:10:57--  https://download.docker.com/linux/static/stable/x86_64/docker-17.12.0-ce.tgz
Resolving download.docker.com (download.docker.com)... 2600:9000:21c7:ac00:3:db06:4200:93a1, 2600:9000:21c7:a200:3:db06:4200:93a1, 2600:9000:21c7:5c00:3:db06:4200:93a1, ...
Connecting to download.docker.com (download.docker.com)|2600:9000:21c7:ac00:3:db06:4200:93a1|:443... connected.
HTTP request sent, awaiting response... 200 OK
Length: 34272897 (33M) [application/x-tar]
Saving to: ‘docker-17.12.0-ce.tgz’

docker-17.12.0-ce.tgz                               100%[=. .. =>]  32,68M  ?,79MB/s    in ?,8s

2019-12-15 17:11:02 (6,76 MB/s) - ‘docker-17.12.0-ce.tgz’ saved [34272897/34272897]
```

### Obtain Meta-Information

As of time of writing, a package in pipelines for the docker client
binary contains a bit more meta-information than the download location:

* The name of the package - to identify it, in this how-to we take
  `docker-17.12.0-ce-linux-static-x86_64`, the name should be portable
* The URI - in this how-to it is the https URL of the download package
* The SHA-256 checksum of the download package - to verify if the
  download was successful (complete)
* The path of the docker binary inside the download package - this path
  is needed for extraction
* The SHA-256 checksum of the docker binary - to verify extraction from
  the package was successful (complete)

To create the SHA-256 checksum of the download package:

```bash
$ shasum -a256 docker-17.12.0-ce.tgz
692e1c72937f6214b1038def84463018d8e320c8eaf8530546c84c2f8f9c767d  docker-17.12.0-ce.tgz
```

Next look into the download package for the path of the docker client:

```bash
$ tar -tvf docker-17.12.0-ce.tgz
drwxr-xr-x 0/1000            0 2017-12-27 21:13 docker/
-rwxr-xr-x 0/1000      4320064 2017-12-27 21:13 docker/docker-containerd-shim
-rwxr-xr-x 0/1000     15433832 2017-12-27 21:13 docker/docker-containerd
-rwxr-xr-x 0/1000      7550928 2017-12-27 21:13 docker/docker-runc
-rwxr-xr-x 0/1000     19938610 2017-12-27 21:13 docker/docker
-rwxr-xr-x 0/1000       760040 2017-12-27 21:13 docker/docker-init
-rwxr-xr-x 0/1000     12773768 2017-12-27 21:13 docker/docker-containerd-ctr
-rwxr-xr-x 0/1000      2517244 2017-12-27 21:13 docker/docker-proxy
-rwxr-xr-x 0/1000     46366152 2017-12-27 21:13 docker/dockerd
```

It normally is `docker/docker` and so it is in this how-to. Locate the
path and create a SHA-256 checksum of it:

```bash
$ tar -xf docker-17.12.0-ce.tgz -O docker/docker | shasum -a256
c77a64bf37b4e89cc4d6f35433baa44fb33e6f89e2f2c3406256e55f7a05504b  -
```

Now all required meta-data has been obtained to create a package.

### Encode Meta-Information into a YAML File

The only thing left to fully produce a package file is to encode the
collected information as YAML:

```bash
$ <<'YAML' cat > docker-17.12.0-ce-linux-static-x86_64.yml
---
# this file is part of pipelines
#
# binary docker client package format
#
name: docker-17.12.0-ce-linux-static-x86_64
uri: https://download.docker.com/linux/static/stable/x86_64/docker-17.12.0-ce.tgz
sha256: 692e1c72937f6214b1038def84463018d8e320c8eaf8530546c84c2f8f9c767d
binary: docker/docker
binary_sha256: c77a64bf37b4e89cc4d6f35433baa44fb33e6f89e2f2c3406256e55f7a05504b
YAML
```

When done you find a `.yml` file with the name in the current directory.

> ***Note:*** You can find another description of each part of the
> package format in the commented package after `composer install`:
> [lib/package/docker-42.42.1-binsh-test-stub.yml][1]

[1]: ../lib/package/docker-42.42.1-binsh-test-stub.yml

This package `.yml` file can already be used for a first test to see if
it works. Just run `pipelines` with the `--docker-client` option having
the path to the `.yml` file as argument:

```bash
$ bin/pipelines --pipeline custom/docker \
	--docker-client docker-17.12.0-ce-linux-static-x86_64.yml
...
 +++ docker client install...: Docker version 17.12.0-ce, build c97c6d6

+ docker version --format {{.Client.Version}}
17.12.0-ce
...
```

This runs pipelines with a pipeline that has the docker service and
therefore injects the docker client binary from the package `.yml` file
provided by `--docker-client` argument. In this case the projects'
custom pipeline named "_docker-in-docker_" (id: `custom/docker`).

The benefit of having a `.yml` package file is that all the meta-
information is already documented, including on how to obtain the file.

This includes benefiting for a user-wide store of checksum/hash'ed
files.

The tar package files are _cached_ in the `XDG_CACHE_HOME` directory,
which by default is:

```bash
~/.cache/pipelines/package-docker
```

As long as the user decides to keep the cache, this spares downloading
of the tar package again and again.

The docker client binaries are not cached but kept and _stored_ so that
they are available in user-land until the user decides to remove the
application in the `XDG_DATA_HOME` directory:

```bash
~/.local/share/pipelines/static-docker
```

### Add Packages to Pipelines Project

Instead of having a `.yml` package somewhere on the local disk it is
better to add it to the pipelines utility itself.

Copy the file into the source directory for package files (*here* from
within a checked-out copy of the pipelines project):

```bash
$ cp -vi docker-17.12.0-ce-linux-static-x86_64.yml lib/package
'docker-17.12.0-ce-linux-static-x86_64.yml' ->  ...
 ... 'lib/package/docker-17.12.0-ce-linux-static-x86_64.yml'
```

To test if the new package name works, invoke Pipelines with the _name_
of this package (not the full file-name or path, e.g. not the path to
the file but just the basename of it excluding the `.yml` extension)
with a pipeline that is making use of the docker service, here exemplary
again with the projects'  custom pipeline named "_docker-in-docker_"
(`custom/docker`):

```bash
$ bin/pipelines --pipeline custom/docker \
	--docker-client docker-17.12.0-ce-linux-static-x86_64
[...]
 +++ docker client install...: Docker version 17.12.0-ce, build c97c6d6

+ docker version --format {{.Client.Version}}
17.12.0-ce
[...]
```

> ***Note:*** If you wish to see a specific package of yours also within
> the upstream distribution feel free to *file a pull-request*.

## Last but not Least: Use a Custom Build Static Docker Client Binary

Pipelines as an integration utility allows to use docker client
binaries directly. For example a binary created from a docker client
development build.

In this variant, no package exists at all and the binary is just given
as the `--docker-client` options argument as path to the binary. No
download will take place, so is no caching nor adding to any local
store (this is all not needed, the file already exists locally).
The package is only temporary so to say and the meta-information is non-
existent.

This mode of operation is very useful for testing any kind of binary as
a docker client.

To distinguish between a package name and binary potentially of that
same name, the `--docker-client` argument needs to have at least a
single slash ("`/`") in the path to the binary.

For example, if the docker client binary is just a local file in the
working directory prefix it with `./` so that pipelines can understand
it is not a package of the same name.

For example with the test stub that is available in pipelines
development:

```bash
$ bin/pipelines --pipeline custom/docker \
	--docker-client test/data/package/docker-test-stub
[...]
 +++ docker client install...: 42.42.1 ( --version )

+ docker version --format {{.Client.Version}}
42.42.1 ( version --format {{.Client.Version}} )
[...]
```

Here a fake `.sh` script is used as the docker client binary which outputs
a bogus version number (`42.42.1`) and all options and arguments with which
it was invoked which makes it suitable for testing purposes.
