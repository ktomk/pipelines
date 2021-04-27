# Working Offline

Running `pipelines` locally allows offline usage.

This is by design, however existing pipelines,
especially when written for a remote service like
Bitbucket, might not work out of the box and need
some preparation.

This is not inherently hard and this document
shows hand-on advice how to do it.

A build is best run locally and a stable one
should not depend (always) on remote resources so
that it has less brittle dependencies and executes
fast on the many re-iterations.

Running the build locally and with no connection
to the internet is a simple test for that.

The `pipelines` utility as well as the pipelines
YAML file-format have plenty of properties to
support a more independent and faster build.

In this document, read about more general
considerations and also find some specific
examples for offline usage of pipelines incl.
containers and what to do with remote files,
dependencies and packages.

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
container and resources in such container
definitions that are very likely not to exist
locally.

That is because building Docker images most often
make use of remote resources that are unavailable
when not connected to the internet.

Remember: Pipelines always prefers locally stored
containers. As long as they are available, no
internet connection is required to use them.

### Build Dependencies

Pulling Docker images might not suffice to run
a pipeline with `pipelines` completely offline.

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

#### Caching of Build Dependencies

Caches within `pipelines` can be used to cache
build dependencies. Add caches to your pipelines
by giving them a distinct name (all caches are
shared on a per project basis) and run the
pipelines before going offline to populate the
cache.

When the build utilities support a cache to fall
back to when being offline, this is an easy way
to not only make pipelines faster by sparing
network round-trips, but also to make them work
with no internet connection.

Read more about caches in [*Working with Pipeline
Caches*](PIPELINES-CACHES.md).

### Remote Build Services

Despite all the offline capabilities, when a
pipeline step fires webhooks or uploads build
artifacts to remote services, this can not
be done offline.

The remote service is a hard dependency of the
pipeline then.

A prominent example for that are for example
deployment steps as most often they need to
interact with remote services.

Depending on your build needs, this may be a
showstopper.

To not be completely blocked from building in an
offline situation, this can be handled by
separating artifacts creation from uploading them
in pipeline steps of their own.

Pipeline *artifacts* can be used to make build
artifacts from one step available in the following
steps.

`pipelines` then can run those pipeline steps
specifically which are not affected when being
offline with the `--step` argument and artifacts
afterwards inspected within your project.

### Test Offline Usage

As there is no guarantee that using pipelines
offline works out of the box, it is useful to
give it a test-run before entering a true
offline situation.

This can be done by actually going offline and
do the development work including running the
pipelines needed for it if being offline is not
your standard way to work (hard to imagine for
some persons nowadays, give it a try if you think
so as it can be very interesting how hard you're
affected by that with benefits working online as
well).

## Special Offline Requirements

For some containers it might be standard to modify
their base-images' environment on a pipeline run.
For example, a PHP image is optimized for a build
pipeline step by installing composer and system
packages like unzip and git.

While it could be better to actually build
dedicated build-images of their own and have them
at hand already in the pipeline step, while
developing intermediate results might be achieved
differently.
Before introducing too much into generic base
container images too early, it is often preferable
as these  things tend to change a lot in the
beginning to do  the groundwork in a less fixed
fashion, for example by extending a pipeline step
script first.

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
(*unless the composer command is installed,
install it*)

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
available as a package. So take this example with
a grain of salt. It is more an example how
HTTP caching *can* be done than how it should be
done.

Strategically it is most often better to prepare
build containers and have their lifecycle well
defined on project level.

* [Individual HTTP Cache example pipelines file](example/individual-http-cache-pipelines.yml)
* [Composer installer wrapper shell script](../lib/pipelines/composer-install.sh)

### Cache Alpine APK Packages

In Alpine based container images, the apk package
manager commonly is in use. By default these
dependencies can not be cached with predefined
Pipeline caches as there is no such apk-cache
predefined.

To nevertheless make use of `apk add <package>`
within a pipeline while being offline, the folder
`/etc/apk/cache` needs to be cached by defining
a cache (here `apk`) and using it:

```yaml
          - apk add bash
          - /bin/bash --version
        caches:
          - apk
definitions:
  caches:
    apk: /etc/apk/cache
```

Once the apk cache is populated, the pipeline runs
offline despite the `bash` package is installed in
the pipeline script.
This first of all works without further ado, after
some time, `apk` tries to re-connect for getting
a fresh package index. This will cost some 2-3
seconds, as `apk` does this two times, see the
*try again later* messages, but as the package
is found in the cache, adding the package works
without any error:

```
...
fetch http://dl-cdn.alpinelinux.org/alpine/v3.12/main/x86_64/APKINDEX.tar.gz
ERROR: http://dl-cdn.alpinelinux.org/alpine/v3.12/main: temporary error (try again later)
fetch http://dl-cdn.alpinelinux.org/alpine/v3.12/community/x86_64/APKINDEX.tar.gz
ERROR: http://dl-cdn.alpinelinux.org/alpine/v3.12/community: temporary error (try again later)
(1/4) Installing ncurses-terminfo-base (6.2_p20200523-r0)
...
```

???- caution "The `--no-cache` Option"
    Do not use `apk add` with the `--no-cache`
    argument. It effectively prevents storing the
    files into the cache. Then, when offline, `apk(1)`
    can not select the package and gives an error
    message showing that apk failed to select the
    package. This is different to a Dockerfile
    that keeps a layer size down. This is normally
    _not_ the case within a pipeline.

Tested with alpine releases 3.12.0 and 3.13.5.

References:

* [Alpine APK Cache example pipelines file](example/alpine-apk-cache-pipelines.yml)
* [Alpine Special Caching Configurations (wiki.alpinelinux.org)](https://wiki.alpinelinux.org/wiki/Alpine_Linux_package_management#Special_Caching_Configurations)
* [Alpine Docker Image](https://hub.docker.com/_/alpine)
* [Alpine Linux](https://alpinelinux.org/)

### Cache Python `pip install`

Enriching a Python based pipeline with pip install
(see as well [*Installing Packages* in the pip
manual][PIP-INST])
can be cached up to full package downloads (which
is a requirement when working offline).

A common example is the use of a
`requirements.txt` file, it works likewise with
package names:

```
$ pip install --user -r requirements.txt
```

Next to the pre-defined `pip` cache, to use pip
offline fully, the installed packages need to be
cached, too.

To not cache all Python packages (that would
include all those a pipeline container image
already contains which would be bulky) pip offers
the `--user` flag to install the packages into the
user-site. That is a separate location, by default
beneath the `$HOME` directory, to install to (see
as well [*User Installs* in the pip manual]
[PIP-USR]).

This "user-site" directory can be obtained with
the following `python` command:

```
$ python -m site --user-site
/home/user/.local/lib/python/site-packages
```

This needs to be obtained in concrete with the
actual pipeline container image, e.g. the docker
image for the mkdocs-material based build that
`pipelines` is using to build the HTML docs which
is in use for this example:

```
$ docker run --rm --entrypoint /bin/sh squidfunk/mkdocs-material \
    -c 'python -m site --user-site'
/root/.local/lib/python3.8/site-packages
```

The output already shows the site-packages
directory which is the cache directory. The
`$HOME` part of it can be abbreviated in pipelines
with the tilde (`~`) or `$HOME`:

```
definitions:
  caches:
    pip-site-packages: ~/.local/lib/python3.8/site-packages
```
(*define a pipeline cache for pip site-packages,
here the user is "root"*)

With such a pip site packages cache in use, the
pip install command can run offline already.

This is not complete for *all* packages. *Some*
pip packages install more, for example commands
into `~/.local/bin`. These need another cache just
for that folder:

```
  caches:
    pip-site-packages: ~/.local/lib/python3.8/site-packages
    localbin: ~/.local/bin
```

That done, for the example of a tailored build
with the `mkdocs-material` image, the python
based pipeline runs flawlessly offline (with all
the benefits of running much faster locally even
when online).

References:

* [Python PIP Cache example pipelines file](example/python-pip-cache-pipelines.yml)
* [PIP Installing Packages][PIP-INST]
* [PIP User Installs][PIP-USR]

[PIP-INST]: https://pip.pypa.io/en/stable/user_guide/#installing-packages
[PIP-USR]: https://pip.pypa.io/en/stable/user_guide/#user-installs
