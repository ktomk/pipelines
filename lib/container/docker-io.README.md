---
# this file is part of pipelines
title: ktomk/pipelines
docker-io: https://hub.docker.com/r/ktomk/pipelines
description: Pipelines - Run Bitbucket Pipelines Wherever They Dock - https://github.com/ktomk/pipelines
categories:
  - Integration & Delivery
  - Developer Tools
---

# Pipelines on Docker Hub

## Docker container images to run Pipelines wherever they dock

Assorted container images on Docker Hub related to the Pipelines utility.

Most (actually: all) of these images you can build your own fresh on your command-line for a short-cut and current versions.

## Tag Namespaces

### `busybox`

The [official Busybox image](https://hub.docker.com/_/busybox) does not run out of the box on [Atlassian Bitbucket Cloud Pipelines](https://bitbucket.org/product/features/pipelines). This tag adds a slim layer (< 200 bytes) introducing a symbolic link `/usr/bin/mkfifo` with a target to `/bin/busybox` which levitates the issue and makes Busybox run.

* [`ktomk/pipelines:busybox`](https://hub.docker.com/layers/ktomk/pipelines/busybox/images/sha256-2ef9a59041a7c4f36001abaec4fe7c10c26c1ead4da11515ba2af346fe60ddac?context=explore) (`sha256:2ef9a59041a7c4f36001abaec4fe7c10c26c1ead4da11515ba2af346fe60dda`)

### `docker-io-*`

Alias for official image tags for OCI/distribution format and manifest schema compatibility (re-tagging):

* `php:5.3` (`sha256:ba952a8970f2fc35e3703b2650495c64d6e015eb52a4ee03f750c69e863b3237`)
* `ktomk/pipelines:docker-io-php-5.3` (`sha256:d03f3d4bd20f99930c923b9f931dd2dd71ac3e51cce8e8ed989b4994561b991c`)

### Other

Most often only temporary tests or examples in context of Pipelines. Find all available definitions in the [`lib/container` sub-folder in the Pipelines repository on Github](https://github.com/ktomk/pipelines/tree/master/lib/container).

Other tags not present are used in examples or (temporary) test container builds (browsing through repository revisions might also reveal more).

## References

* Pipelines Utility on Github: https://github.com/ktomk/pipelines (includes Dockerfiles and other sources for pipeline images on Docker Hub)
* Pipelines Utility Homepage: https://ktomk.github.io/pipelines
