# Working Offline

Running `pipelines` locally allows offline usage.

This is by design, however existing pipelines,
especially when written for a remote service like
Bitbucket, might not work out of the box and needs
some preparation.

Read on about general considerations and also
more specific examples of offline usage of
pipeline incl. containers and what to do with
remote files, dependencies and packages.

## General Considerations

Pipelines are based on Docker containers. Their
images need to be pulled if not stored locally.

### Local Docker Containers

Before going into offline mode, make use of
`--images` and the shell:

```bash
$ pipelines --images | while read -r image; do \
   docker image pull "$image"; done;
```

If there are specific Docker container images in
a project, build all images before going offline
as these at some place at least depend on a base
container that is likely not to exist locally.

Also building Docker images most often make use
of remote resources that are unavailable when not
connect to the internet.

Bottom line: Pipelines always prefers locally
stored containers. As long as they are available,
no internet connection is required to use them.

### Build Dependencies

Pulling Docker images might not suffice to run
a Pipeline with `pipelines` completely offline.

For example when fetching build dependencies
with tools like `npm` or `composer`, these can
not be installed when offline.

First of all `pipelines` is relatively immune to
such an effect if these dependencies were already
installed within the project. As `pipelines`
makes a full copy of the project into the pipeline
container, dependencies might already be
available.

However this might not always be the case and then
offline usage would be broken. A build might also
use different than the development dependencies to
build and therefore would need to fetch while
offline.

#### Caches to the rescue

Caches within `pipelines` can be used to cache
build dependencies. Add caches to your pipelines
by giving them a distinct name (all caches are
shared on a project basis) and run the pipelines
before going offline to populate the cache.

When the build utilities support a cache to fall
back to when being offline, this is an easy way
to not only to make pipelines faster by sparing
network round-trips, but also to make them work
with no internet connection.

Read more about caches in [*Working with Pipeline
Caches*](PIPELINES-CACHES.md).

### Test Offline Usage

As there is no guarantee that using pipelines
offline works out of the box, it is useful to
give it a test-run before entering a true
offline situation.

This can be done by actually going offline and
do the development work including running the
pipelines needed for it if being offline is not
your standard way to work.

## Special Offline Requirements

For some containers it might be standard to patch
their base-image on a pipeline run. For example,
a PHP image is optimized for the build by
installing composer and system packages like
unzip and git.

While it could be better to actually build images
of their own and have them at hand already, while
developing results might be achieved differently.

As `pipelines` is a development utility, find
more specialized offline configurations following.

### Individual HTTP Caching

Obtaining files via http while building can be
common, e.g. when installing more specific tools
or libraries.

One example of that within the `pipelines` project
itself is the composer install script. That is
a simple shell script wrapping the composer
installation.

As it normally acquires the composer installer
from Github, it won't work offline (and would
always need to download the composer installer and
composer itself).

The whole process can be short-circuited within
the pipeline script already by not calling the
composer installer script when composer is already
installed:

```yaml
  script:
    - command -v composer || lib/pipelines/composer-install.sh
```

It would already be installed for example if the
`--keep` option is in use as the container would
be re-used with all the file-system changes.

The `--keep` option most certainly is not
applicable when working offline (despite it could
help in some situations).

Instead the individual http caching is in the
script [`lib/pipelines/composer-install.sh`](../lib/pipelines/composer-install.sh)
itself.

The solution is simple. The script changes the
working directory into a http-caching directory
`~/.cache/build-http-cache` and unless files are
already available they are downloaded relative to
this working directory.

In the pipelines file the cache is defined and
all downloads get automatically cached:

```yaml
definitions:
  caches:
    build-http-cache: ~/.cache/build-http-cache
```

More is not needed to cache things. A simple
shell script can help in maintaining the
procedure.

This is just exemplary. Composer offers its own
docker image. Also the package manager of the
Linux distribution may also have Composer
available as a package.

This example is more about an individual
configuration that can used cached/offline as
well.

* [Individual HTTP Cache example pipelines file](../lib/pipelines/examples/individual-http-cache-pipelines.yml)
* [Composer installer wrapper shell script](../lib/pipelines/composer-install.sh)

### Cache Alpine APK Packages

In Alpine based container images, the apk package
manager normally is in use. By default these
dependencies can not be cached with default
Pipeline caches.

To make use of `apk add <package>` within a
pipeline while being offline, two things need to
be done:

First a symlink needs to be created so that
`/etc/apk/cache` points to the `/var/cache/apk`
directory, e.g. like in the first script line:

```yaml
    - step:
        image: alpine:3.12
        name: alpine apk example
        script:
          - ln -s /var/cache/apk /etc/apk/cache # apk cache requires a symlink
          - apk add bash
```
Then a cache needs to be defined that contains the
apk cache which is the `/var/cache/apk` directory:

```yaml
          - apk add bash
          - /bin/bash --version
        caches:
          - apk
definitions:
  caches:
    apk: /var/cache/apk
```

Once the apk cache is populated, the pipeline
runs offline despite the `bash` package is
installed in the pipeline script.

* [Alpine APK Cache example pipelines file](../lib/pipelines/examples/alpine-apk-cache-pipelines.yml)
* [Alpine Special Caching Configurations (wiki.alpinelinux.org)](https://wiki.alpinelinux.org/wiki/Alpine_Linux_package_management#Special_Caching_Configurations)
* [Alpine Docker Image](https://hub.docker.com/_/alpine)
* [Alpine Linux](https://alpinelinux.org/)
