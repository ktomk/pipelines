# Working with Pipeline Caches

Caches within `pipelines` can be used to cache
build dependencies.

The `pipelines` utility has caches support since
version 0.0.48 (July 2020), _docker_ was always
"cached" as it is handled by docker on your host.

What is the benefit of a cache when running
`pipelines` locally?

Pipeline caches can help to speed-up pipeline
execution and spare network round-trips and
computation for the price of your (precious) disk
space.

Once populated, caches even allow to run
`pipelines` completely offline from your local
system (if the tools in the build script support
offline usage, e.g. _composer_ does fall-back to
the cache in case remote resources are offline/
unreachable, _composer_ is also much faster when
it can install from cache - just to use it as an
example, there is no inherit requirement for
_composer_ with `pipelines`).

To ignore caches for a pipeline run, add the
`--no-cache` switch which effectively does not
use caches and establishes the old behaviour.

Most often caches are for caching build
dependencies. For example Composer packages, Node
modules etc. and make use of caches as well for
your very own specific build requirements as well.

## Predefined Caches

All predefined caches are supported as documented
in [Bitbucket Pipelines file-format][BBPL-CACHES]
\[BBPL-CACHES] which makes it straight forward
to configure a dependency cache just by its name:


| Cache Name | Path                             |
| ---------- |----------------------------------|
| docker     | *handled by docker on your host* |
| composer   | `~/.composer/cache`              |
| dotnetcore | `~/.nuget/packages`              |
| gradle     | `~/.gradle/caches`               |
| ivy2       | `~/.ivy2/cache`                  |
| maven      | `~/.m2/repository`               |
| node       | `node_modules`                   |
| pip        | `~/.cache/pip`                   |
| sbt        | `~/.sbt`                         |

Predefined caches and new ones can be (re-)defined
by adding them to the `caches` entry in the
`defintions` section of `bitbucket-pipelines.yml`
file.

## Example of Caches in a Pipelines Step

The following example shows a custom pipeline that
is using a very dated PHP version (5.3) to run
the projects phpunit test-suite.

This requires to remove some project dependencies
that are managed by composer and install an also
dated but PHP 5.3 compatible version of Phpunit
(4.x), shown in the following YAML.

Having the _composer_ cache allows to cache all
these outdated dependencies, the pipeline runs
much faster as composer installs the dependencies
from cache, making integration testing against
specific configurations straight forward:

```yaml
pipelines:
  custom:
    unit-tests-php-5.3:
      - step:
          image: cespi/php-5.3:cli-latest # has no zip: tomsowerby/php-5.3:cli
          caches:
            - composer
            - build-http-cache
          script:
            - command -v composer || lib/pipelines/composer-install.sh
            - composer remove --dev friendsofphp/php-cs-fixer phpunit/phpunit
            - composer require --dev phpunit/phpunit ^4
            - composer install
            - vendor/bin/phpunit # --testsuite unit,integration by default w/ phpunit 4.8.36
definitions:
  caches:
    build-http-cache: build/store/http-cache
```

It is also an example of a cache named `build-http-cache`
which is caching the path `build/store/http-cache`, a
relative path to the *clone directory* which is also
the pipelines' default working directory (and the
project directory).

!!! note
    If `--deploy mount` is in use
    and a cache path is relative to the clone directory,
    regardless of caches, the dependencies as within the
    mount, will populate the local directory.
    This may not be wanted, consider to not use
    `--deploy mount` but (implicit) `--deploy copy`
    _and_ appropriate caches or artifacts.

Cache paths are resolved against the running
pipelines' container, the use of `~` or `$HOME` is
supported.

## Differences to Bitbucket Pipelines

`pipelines` caches should be transparent compared
to running on Bitbucket from the in-container
perspective, the pipeline should execute and cache
definitions work as expected.

However as running local, there are some
differences in how caches are kept and updated.

### Invalidating the Cache

Bitbucket Pipelines keeps a cache for seven days,
then deletes/drops it. A cache is only created and
never updated. As after seven days it will be
deleted (or if deleted manually earlier), it will
be re-created on the next pipeline run.

In difference, the `pipelines` utility keeps
caches endlessly and _updates_ them after each
(successful) run.

This better reflects running pipelines locally as
it keeps any cache policy out of the equation,
there aren't any remote resources to be managed.

Having up-to-date caches after each successful
local pipeline step run is considered an
improvement as caches are less stale. The meaning
of remote is directly in the meaning of the
dependencies and not with the indirection that the
pipeline is run in some cloud far away. Shift
left (iterate and get results faster).

### The Docker Cache

Another difference is the _docker cache_. It is
always "cached" as it is `docker` on your host and
falls under your own configuration/policy - more
control for you as a user. More interoperable
with your overall docker. Makes containers (and
Docker) more transparent.

### Resource Limits and Remote Usage

There are no resource limits per cache other than
the cache is limited by your own disk-space and
other resources you allow docker to use.

Take this with a grain of salt, there is no
specific handling that makes `pipelines` fatal or
panic if there is not enought disk-space left.

`pipelines` will fail if your disk runs out of
 space, but you normally want to know earlier
(unless a throw-away system), so if you're running
short on disk-space, consider for what to use the
(not) remaining space.

Apropos locally:

`pipelines` itself does not upload the cache files
to any remote location on its own. However if the
folder it stores the tar-files in is being shared
(e.g. shared home folder), sharing the cache may
happen (see as well the next section, especially
if this is not your intention).

So just in case your home directory is managed and
will fill-up some remote storage towards its
limits talk with your sysadmin before this happens
and tell her/him about your needs upfront. Disk
space is relatively cheap these days but if
managed, it is better to raise requirements
earlier than later.

### Where/How Pipelines Stores Caches

Caching is per project by keeping the cache as a
tar-file with the name of the cache in it:

```
${XDG_CACHE_HOME-$HOME/.cache}/pipelines/caches/<project>/<cache-name>.tar
```

In case you find it cryptic to read:

This respects the *[XDG Base Directory Specification
][XDG-BASEDIR]* \[XDG-BASEDIR]
and keeps cache files `pipelines` creates within
your home directory unless `XDG_CACHE_HOME` has
been set outside your home directory.

This is the directory where _all_ of the cache
files will end up, there is one tar-file for each
cache.

So there is one tar-file per each pipeline cache
per project.

Paths in the tar file are relative to the cache
path in the container.

Having a central directory on a standard system
path comes with the benefit that it is easy to
manage centrally.

Be it for your local system or for remote systems.

Remote? For example, if `pipelines` is used in a
remote build environment/system, the projects
pipeline caches can be cached again by the remote
build system with ease.

For example when running in a CI system like [Travis
CI][TRAVIS-CI] \[TRAVIS-CI], caches can be easily
retained between (remote) builds by caching the
entire `XDG_CACHE_HOME`/`HOME` cache folder.

Apply any caching policy of your own needs then
with such an integration to fit your expectations
and (remote) build requirements.

Also if you want to move the cache out of the home
directory, you can make use of the
`XDG_CACHE_HOME` environment variable to have it
at any location you like.

## Cache Operations

Support for caches in the `pipelines` utility is
sufficient for supporting them in the file-format
(read: minimal), this leaves it open to maintaining
them on your system (read: done by you). Do not
fear, it normally is merely straight forward for
simple things like dropping caches and leaves you
with many more options for inspecting caches,
populating caches (warming them up), even merging
into caches, sharing across projects and merging
back into your very own file-system. Your system,
your control.

As caches are based on standard tar-files, common
and more advanced tasks can be done by interacting
with all the (shell) utilities you love and hate
and at the end of the day know best.

Following are a couple of usage examples for tasks
`pipelines` might look lacking, it is not that
you should not file any kind of feature request or
missing option, it is just that `pipelines` will
never be able to provide all these capabilities
on its own, so if you find them helpful or
inspiring or both, this is what they are for:

### List and Inspect Caches

To list all caches on disk including size information
the `du` utility can be used:

```
$ du -ch ${XDG_CACHE_HOME-$HOME/.cache}/pipelines/caches/*/*.tar
35M     /home/user/.cache/pipelines/caches/pipelines/build-http-cache.tar
69M     /home/user/.cache/pipelines/caches/pipelines/composer.tar
104M    total
```

As each cache is a tar-file, any utility showing
information about files will give a good summary
already.

For more details on any specific cache, the `tar`
utility is of use:

```
$ tar -vtf ${XDG_CACHE_HOME-$HOME/.cache}/pipelines/caches/pipelines/build-http-cache.tar
drwxrwxr-x 1000/1000         0 2020-07-29 01:12 ./
-rw-r--r-- 1000/1000       629 2020-07-05 10:53 ./.gitignore
-rwxrwxrwx 0/0         1969526 2020-07-24 01:42 ./composer.phar
-rw-rw-r-- 1000/1000  34272897 2019-12-15 22:58 ./docker-17.12.0-ce.tgz
```

_(using gnu tar here, check your very own tar
version for all the options as these might differ
depending on which flavor)_

This example also shows that it is possible to
migrate caches by changing the _cache definition
path_ as all paths are relative to the cache path
(they all start with `./` in the tar-file).

#### Migrating from Project Directory to Caches

_(this section certainly is of special-interest
and focus to detail on migrating to
caches locally from not using caches earlier incl.
being in an offline scenario)_

In the example above also an intersection of
copying files into the container (`--deploy copy`)
and with downloading new files in the container (
here `composer.phar`) is shown (without caches,
copy back into the project via _artifacts_ was
in effect earlier).

This is because previously there was no caching/
were no caches and the project directory has been
used as a store for offline copies of remote files;
used here as an example to migrate away from such a
workaround.

The example combines a workaround for a `pipelines`
version with no cache support (and no docker client
support, using the path `build/store/http-cache`)
but running pipelines offline regardless.

Technically this is not necessary any longer, but
it shows how this has been done without any caches
support in `pipelines` earlier.

As now caches are supported, the cache can be
moved into `~/.cache/build-http-cache` (exemplary)
by changing the path in the `definitions` section.

As the name of the cache does not change - just the
path in the definition - after updating all affected
pipelines that used the previous workaround where
they kept their own "cache", only the path in
the cache definition needs to be changed.

It only needs one run where artifacts and the
cache(s) path(s) align. Afterwards as the cache(s)
are filled, they can be moved to a different
location outside of the clone directory by
changing the cache paths of the definition(s) and/
or removing the artifact(s) definition for these.

See as well [Populate Caches](#populate-caches)
below.

### Dropping a Cache

To drop a cache, removing the tar file in the
cache directory suffices.

!!! warning
    Dropping a cache may prevent executing a
    pipeline successfully when being offline. To
    test dropping a cache, move the cache tar
    file to a backup location before removing it
    from the filesystem so in case of error
    iterating the pipeline run fails, it can be
    moved back.

Example (removal):

```
$ rm -vi -- ${XDG_CACHE_HOME-$HOME/.cache}/pipelines/caches/pipelines/build-http-cache.tar
rm: remove regular file '/home/user/.cache/pipelines/caches/pipelines/build-http-cache.tar'? y
removed '/home/user/.cache/pipelines/caches/pipelines/build-http-cache.tar'
```

Here the cache named _build-http-cache_ from the
_pipelines_ project is dropped. Commanded `rm` for
interactivity it requires to confirm the deletion.
Additionally verbosity dictates to show any file
removal.

Modify the `rm` command as you see fit to drop a
cache (or all caches of a project or even all
projects).

Use the `mv` command to (re)-move the cache by
moving the file to a different location in the
local file-system.

### Populate Caches

It is possible to pre-populate caches by importing
files/directories from the local system (aka
warming up caches).

This works by creating a tar-file with relative
paths on the container cache directory.

For example in an offline situation when the cache
does not yet exist.

The following section demonstrates it by the
example of [_composer_][COMPOSER] \[COMPOSER].

#### Populate Composer Cache from Your Host

To populate the composer cache from your local
system, all that needs to be done is to create
a tar-file at the right place with the contents
of your (local) composer cache.

Mind that the project-name as well as the local
cache directory of composer can differ:

!!! warning
    Utilities like `tar` in the following
    example do overwrite existing files. Better
    safe than sorry, backup the cache tar-file
    first in case it already exists. This is
    especially noteworthy for offline scenarios
    as once dependencies are lost, your build
    breaks unless you're able to retain them from
    the wide-area network (going online) again.

```
$ tar -cf ${XDG_CACHE_HOME-$HOME/.cache}/pipelines/caches/pipelines/composer.tar \
  -C ~/.cache/composer/ .
```

#### Merge Back to Your Host

Similar it is also possible to import/merge caches
back into the local system by un-tarring to a
local directory:

```
$ tar -xf ${XDG_CACHE_HOME-$HOME/.cache}/pipelines/caches/pipelines/composer.tar \
  -C ~/.cache/composer/
```

!!! note
    It depends on the underlying utility if merging
    caches is possible or not. Here for `composer`
    it is, for other utilities this might vary on
    their robustness and cache structure.

Merging into tar files is similarly possible,
see your tar manual for append operations.

## Alternatives

Next to caches pipelines file-format support - and
independent to it - containers can be kept and
re-used (`--keep`) which can help with faster re-
iterations when writing pipelines by the price of
less isolation (just say `--no-cache` with
`--keep` until caches are ready to implement).

As kept containers are with all the changes to the
file-system, this effectively caches all build
dependencies on the fly w/o specifying/defining
caches first.

As artifacts are copied back into the project
directory (at least at a successful last step)
making use of artifacts also adds some options.

## References

* \[BBPL-CACHES]: https://support.atlassian.com/bitbucket-cloud/docs/cache-dependencies/
* \[COMPOSER]: https://getcomposer.org/
* \[TRAVIS-CI]: https://travis-ci.org/
* \[XDG-BASEDIR]: https://specifications.freedesktop.org/basedir-spec/basedir-spec-latest.html

[BBPL-CACHES]: https://support.atlassian.com/bitbucket-cloud/docs/cache-dependencies/
[COMPOSER]: https://getcomposer.org/
[TRAVIS-CI]: https://travis-ci.org/
[XDG-BASEDIR]: https://specifications.freedesktop.org/basedir-spec/basedir-spec-latest.html
