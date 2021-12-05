# Getting Started with Pipelines

This is a short "getting started" guide. You can find out quickly if you
have all common requirements available to get `pipelines` up and
running.

As a cherry on top, you'll find the browse- and search-able
documentation on your system afterwards.

* Duration: ca. 1-3 minutes (depends on download speed)
* Location: on the command-line
* Utility Dependencies: `php`, `git`, `composer`and `docker`

## Install Pipelines

The heavy lifting of pipelines installation is done by
`composer create-project` in this tutorial:

~~~shell
$ composer create-project -n --prefer-source --keep-vcs --no-dev \
  ktomk/pipelines
Creating a "ktomk/pipelines" project at "./pipelines"
...
Generating autoload files
$  cd "$PWD/pipelines"
~~~

The `pipelines` utility is available at `bin/pipelines` now. Let's
display the installed version:

~~~
$ bin/pipelines --version
pipelines version 0.0.60
~~~

If so far everything worked it confirms a working PHP version with a
Composer and Git installation.

## Run a Pipeline to Generate the HTML Documentation

Time to put some pressure on a pipeline. Let's generate the manual from
sources and open it:

~~~shell
$ bin/pipelines --pipeline custom/html-docs
+++ step #1

    name...........: "build html-docs with mkdocs"
...
+++ copying artifacts from container...
$ xdg-open build/html-docs/index.html
~~~

This effectively tests if Docker is available. On the first run it will
take a bit more time as remote resources are downloaded (build container
and dependencies) and installed.

These are cached afterwards, which is why any future run will be faster.

---

In this little _getting started tutorial_ we could see the following:

* A working PHP + Composer setup for pipelines
* Installed pipelines with Composer + Git
* Docker (or similar `docker(1)`) is configured for pipelines
* A container is pulled and build dependencies are downloaded during
  a pipeline run. These are _cached_.
* Doing real work with a pipeline to transfer the HTML documentation
from sources. These _artefacts_ are then served fresh on platter.

---

## Next Steps Suggestions

* Read: Continue reading the documentation you just build
* Setup: Install `pipelines` in your `$PATH`
* Exercise: Run a "Hello World" pipeline from your shell as a one-liner
