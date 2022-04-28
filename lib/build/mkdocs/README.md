# Pipelines HTML Documentation Build

The `pipelines` documentation is a collection of HTML files. There are
two flavours:

1. Static HTML files for local browsing (`html-docs`)
2. The project homepage on Github-Pages (`gh-pages`)

It is build with [Mkdocs][MKDOCS] \[MKDOCS] and the
[Material for Mkdocs theme][MKDOCS-MATERIAL] \[MKDOCS-MATERIAL].

## Generating HTML-Docs for local browsing

The HTML-Docs for local browsing are created by a custom pipeline:

~~~
$ bin/pipelines --pipeline custom/html-docs
~~~

After running this pipeline the documentation is ready to browse
at `build/html-docs/index.html`.

## HTML Documentation Overview

The HTML documentation is generated from Markdown (`.md`) files.

Different to a vanilla Mkdocs project, in pipelines the documentation
files are within the project repository from its root and not
in a sub-folder named `docs`. This is different to the default Mkdocs
layout in so far, that the default expects a `docs` folder containing
the markdown files.

In pipelines, this `docs` folder is created when building the HTML
documentation from a small symlink-farm of the Markdown documents from
the project root.

At the same time some additional content is generated from other project
files.

All this is wired in a Makefile and for the standard HTML docs build
available as [a custom pipeline](#generating-html-docs-for-local-browsing).

## Getting Started with the HTML-Docs Build

The html documentation is build with `make` by the `Makefile` in the
`lib/build/mkdocs` directory:

```
$ cd lib/build/mkdocs  # mkdocs based build directory
$ make                 # build standard html-docs
...
```
There are two standard goals:

1. ***html-docs*** - HTML documentation that can be opened in a browser
   from the file-system. It includes a javascript based search and keeps
   online resources (e.g. remote images, fonts or scripts) out of it.
   _(default goal)_
2. ***gh-pages*** - the online version as on Github-Pages, the pipelines
   project homepage. This includes online resources like badges in the
   `README.md` and Github API requests.

For these artifacts the makefile goals:

```shell
$ make html-docs
```

and

```shell
$ make gh-pages
```

Results are placed into the `build/html-docs` and `build/gh-pages`
directories accordingly.

### Development Build

Most of the documentation markdown files can be edited and the
documentation is build on-the-fly while served from a running
test-server:

```shell
$ make serve
```

Here again it can be chosen as from the two variants as additional
targets:

```shell
$ make serve-gh # gh-pages goal
```

and:

```shell
$ make serve-html # html-docs goal
```

## Build and Make Requirements

To develop the html documentation use the `Makefile` in
the `lib/build/mkdocs` directory for more fine grained control of the
build incl. installing the build tools and control other prerequisites.

The build requires:

* Python3 (as `python3` by default, see `python` build parameter)
* PIP
* PHP (with the JSON extension at least)
* Composer
* Git
* GNU Make
* Bash
* A file-system that supports relative symbolic links

Mkdocs, plugins and themes will be installed if missing.

## Specifics of the Mkdocs based Build

Even both Mkdocs and the Material for Mkdocs theme are pretty well
standardized, developing is a moving target much so often. Here
some of the details that appear noteworthy about creating the
documentation in the `pipelines` project:

### Theme Modifications

_Mkdocs_ offers a modular way to modify the theme. And _Material for
Mkdocs_ is used as *the* theme. Still some little modifications are
done.

This is most of all controlled in the standard `mkdocs.yml` _Mkdocs_
configuration file pointing to resources in the file-system inside
the mkdocs library build (`lib/build/mkdocs`) folder:

* SVG logo and favicon (in `docs/assets`)
* CSS Tweaks (in `docs/assets/extra.css`):
    * Vertical scrolling of code-blocks that exceed a certain height.
    * Touch-up of the CSS for snappier table appearances.
    * Tweak on the footer icons to make Material for Mkdocs stand out.

### Using Ghp-Import to publish to Github-Pages

The [`ghp-import`][GHP-IMPORT] \[GHP-IMPORT] command can be used to
publish a static site to Github-Pages. By default Mkdocs has publishing
to Github-Pages build-in making use of the `ghp-import` library just
with its own utility command.

For a more tailored publishing, `ghp-import` is installed as a utility
on its own so that the `gh-pages` target can contain the appropriate
tree layout which is not root but the `docs` folder as the publishing
source and it allows to prominently present a `README.md` of its own
when browsing the publishing branch on Github.

This can be easily handled by using the `ghp-import` utility standalone.

The publishing script is `script/publish.sh`.

* [Configuring a publishing source for your GitHub Pages site](https://docs.github.com/en/pages/getting-started-with-github-pages/configuring-a-publishing-source-for-your-github-pages-site) (docs.github.com)

### Making the Mkdocs Build Deterministic

A few modifications were necessary to make the Mkdocs build and the
publishing to Github-Pages more deterministic:

* Gzip file timestamp in `sitemap.xml.gz` (gzip, mkdocs build)
* `<lastmod>` timestamps in `sitemap.xml` (mkdocs build/theme)
* Timestamps in publishing commit (git)
* File content (pipelines)

#### Timestamp in `sitemap.xml.gz`

The gzip version of the `sitemap.xml` file contains the time-stamp of
the `sitemap.xml` file which is generated when the docs are build.

Recompressing the file without the name in post processing levitates
this (`gzip -n` or `gzip --no-name`).

This `gzip(1)` build post-processing is not necessary any longer since
[Mkdocs Version 1.1.1 (2020-05-12)]. Mkdocs `sitemap.xml.gz` file
timestamp has support at build time for the [`SOURCE_DATE_EPOCH`
environment parameter] in [`fa5aa4a2` / #2100] via [`7f30f7b8` / #1010]
and [`3e10e014` / #939].

[Mkdocs Version 1.1.1 (2020-05-12)]: https://www.mkdocs.org/about/release-notes/#version-111-2020-05-12
[`SOURCE_DATE_EPOCH` environment parameter]: https://reproducible-builds.org/specs/source-date-epoch/
[`fa5aa4a2` / #2100]: https://github.com/mkdocs/mkdocs/commit/fa5aa4a26efc2a0dc3878b41920eaa39afc8929b
[`7f30f7b8` / #1010]: https://github.com/mkdocs/mkdocs/pull/1010/commits/7f30f7b8343d3b241d4e7162093da5ca6642971f
[`3e10e014` / #939]: https://github.com/mkdocs/mkdocs/commit/3e10e014b63dfcc85a30e6198da00677f7eefb24

#### Superfluous `<lastmod>` elements in `sitemap.xml`

On build time, each sitemap entry gets a `<lastmod>` element entry
with the current date (`YYYY-MM-DD`).

Removing those `<lastmod>` elements (done in  overriding the
`sitemap.xml` template) prevents running into this problem.

In general the `<lastmod>` element is not mandatory, and in concrete it
has very little meaning, as it contains build time, so all last
modification dates are the same and are therefore redundant.

As an alternative to drop the `<lastmod>` element, it should be possible
as well to make use of Mkdocs `update_date` / [`build_date_utc`] and/or
the [mkdocs-git-revision-date-plugin] (not tested).

[`build_date_utc`]: https://www.mkdocs.org/dev-guide/themes/#build_date_utc
[mkdocs-git-revision-date-plugin]: https://github.com/zhaoterryy/mkdocs-git-revision-date-plugin

* [Custom sitemap.xml template?](https://groups.google.com/forum/#!topic/mkdocs/UaXpZAa8B-Y) (Mkdocs groups.google.com)
* [Hide empty lastmod tags in sitemap.xml #1465](https://github.com/mkdocs/mkdocs/pull/1465) (Mkdocs github.com)
* [Add a "Last Updated" field to .md pages](https://groups.google.com/g/mkdocs/c/0iij2vNZZwE) (Mkdocs groups.google.com)

#### Timestamp Github Publishing Commit

The `Makefile` build sets the publishing commit timestamp to the date in
git of the revision the docs are build from.

This ensures that for each documentation revision (content) the
timestamp is the same. It also establishes the property that
documentation build from the same content (and same python dependencies)
results in the same commit hash.

#### Reproducible Pipelines Files

When building from a revision/checkout normally all documentation files
from the project are of that revision and therefore the build can be
deterministic.

In `pipelines`, when the project is checked-out and installed, some
files are generated. When any of such files is taken into the
documentation build, then these files need to generate always the same
by their underlying revision.

In the project one such special case is a test package for the docker
client binary.

This test-package (`lib/package/docker-42.42.1-binsh-test-stub.yml`)
references an artifact (`.tar.gz` file) with a SHA-256 hash. With each
installation, the hash of the package file changed and therefore the
test-package `.yml` file.

Exemplary fix was to make the tar package anonymous and set the
timestamp to begin of the UNIX epoch (`--owner=0 --group=0
--mtime='UTC 1970-01-01'` in gnu tar).

Additionally gzip must be commanded to do not save the original file
-name and -timestamp (`gzip -n` or `gzip --no-name`).

### Migrating URLs on Github-Pages

When the initial prototype of the html-docs landed on Github-Pages and
since that day made its way into remote references, it was much later
realized that the wrong Mkdocs URL path layout was in use
(`directory_urls: true`) while it should have been
`directory_urls: false`. This layout saves some of the files and
directories to be created. And results in a better representation of
the documents.

Therefore a migration was due from the old to the new layout. It could
be successfully established. Over a period of ca. a month all search
engine entries were using the correct URL.

#### Migrating with Rel Canonical

Technically this has been done by ensuring the old (wrong) files contain
a `<link href="..." rel="canonical>` with the reference to the new
(correct) file \[[Canonical Link Element]].

Additionally all old (wrong) links within all old (wrong) files are
re-written to their new (correct) links.

All such fixed files are then merged into the correct build.

So in detail, the `gh-pages` build are two builds. First the correct
one (the current one). Second, the wrong one (the old). The later is
done into a directory of it's own. Then the resulting files are
processed, loaded into memory and when a fixer applies are fixed,
re-compressed and written out into the output directory of the first
build resulting in a partial merge with all old URIs (so not having
any 404s) with appropriate pointers to their correct URIs via the
canonical link.

#### Migrating with Redirects

Albeit migrating with redirects could have been an option as well, but
the _Mkdocs redirects plugin_ does not offer any of the required
functionality. It is not aware of the different `directory_urls` path
layouts for the redirect it offers.

Working with canonical link relations was considered the alternative
then and as it started quickly with simple search and replace operations
it was preferred over redirects.

It has been proven as the right decision as the search engine results
(SERPS) show. Assumably the redirections would have led to a similar
result.

The script to modify the links in all old index.html files uses a
HTML parser and modifier, which is better than the first search and
replace approach.

It could be extended with another command-line option to add meta-
redirects for these files, too.

This could be done next to be finally able to remove the old and wrong
HTML pages completely without or only very little negative effect.

## Mkdocs-Material Fonts

The webfonts are disabled, if the _Roboto_ and _Roboto Mono_ fonts are
available locally, they will be used.

This is done via `theme: fonts: false` in mkdocs-material and by
`font-local.css` that is `@import` in `extra.css`.

## References

* \[MKDOCS]: https://www.mkdocs.org/
* \[MKDOCS-MATERIAL]: https://squidfunk.github.io/mkdocs-material/
* \[GHP-IMPORT]: https://pypi.org/project/ghp-import/
* \[Canonical Link Element]: https://en.wikipedia.org/wiki/Canonical_link_element

[MKDOCS]: https://www.mkdocs.org/
[MKDOCS-MATERIAL]: https://squidfunk.github.io/mkdocs-material/
[GHP-IMPORT]: https://pypi.org/project/ghp-import/
[Canonical Link Element]: https://en.wikipedia.org/wiki/Canonical_link_element
