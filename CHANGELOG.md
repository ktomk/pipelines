# Change Log
All notable changes to Pipelines will be documented in this file.

The format is based on [Keep a Changelog] and Pipelines adheres to
[Semantic Versioning].

[Keep a Changelog]: https://keepachangelog.com/en/1.0.0/
[Semantic Versioning]: https://semver.org/spec/v2.0.0.html

## [0.0.57] - 2021-05-14
### Change
- Phar file build from sources and pipelines running from sources now
  show the same `--version` format.
### Fix
- Restore build reproducibility when building the phar file with
  composer 2, since [0.0.55](#0055---2021-05-02)

## [0.0.56] - 2021-05-13
### Add
- Show `--step-script`, optionally by `<id>` and `<step>`
- Build the phar file with PHP 5.3 (as well, all 5.3-8.1 reproducible)
### Change
- Fail early if git command is n/a in phar build
### Fix
- Fix pipelines `--version` when installed via composer with composer
  version 2.0.0 or higher, since [0.0.51](#0051---2020-12-09)
- Fix very rare php timezone warning in the phar build when modifying the
  timestamps, since [0.0.1](#001---2018-01-10)
- Fix phar build error in bare and isolated repository on removing
  non-existing development package stub, since [0.0.1](#001---2018-01-10)
- Fix Phpstorm meta for the phpunit based testsuite (mocks, [WI-60242])

[WI-60242]: https://youtrack.jetbrains.com/issue/WI-60242

## [0.0.55] - 2021-05-02
### Change
- Build with Composer 2; pin composer version to 2.0.13 (from 1.10.17)
### Fix
- `$PHP_BINARY` support while making test stub packages, since
  [0.0.25](#0025---2019-12-30)
- PHP-Binary detection in meagre environments, since [0.0.19](#0019---2019-04-02)

## [0.0.54] - 2021-04-17
### Add
- Support changed docker remove behaviour (Docker 20.10)
### Fix
- Exit on docker service definition, since [0.0.37](#0037---2020-05-30)  (#10) (thanks [Manuel])

[Manuel]: https://github.com/ortizman

## [0.0.53] - 2021-01-03
### Add
- Show `--validate` file-name/ -path
### Change
- Reduce of past-tense in change log headlines
### Fix
- Phar uploads for the releases 0.0.52 and 0.0.51 on Github as they
  did not match the original signatures from date of release due to an
  error in the build re-building them within a dirty repo (old files
  show "+" at the end of their version number (0.0.52+; 0.0.51+),
  correct phar files do not.
- Shell tests in CI taint phar build, since [0.0.51](#0051---2020-12-09)
- Validating w/ empty file-name (`--validate=`), since
  [0.0.44](#0044---2020-07-06)
- Changelog missing links to "since x.x.x" revisions
- Changelog missing dash "-" in last revision headline

## [0.0.52] - 2020-12-31
### Change
- Tests expect Xdebug 3 by default, run `$ composer ppconf xdebug2`
  for Xdebug 2 compatibility.
- Continue [migration from Travis-CI to Github-Actions][Run-Travis-Yml]
- Rename tests folder to test to streamline directory names.
### Fix
- Composer which script compatibility with composer 2 < 2.0.7.
- Quoting new-line character at the end of argument, since
  [0.0.1](#001---2018-01-10)
- Phpunit test-case shim for invalid-argument-helper since Phpunit
  6.x, missing in [0.0.51](#0051---2020-12-09)

## [0.0.51] - 2020-12-09
### Add
- Support for PHP 8
- Support for Composer 2
- [Migration from Travis-CI to Github-Actions][Run-Travis-Yml]
- [Documentation about development][Dev-Doc]
- (source only) pipelines `--xdebug` option to run within php with
  xdebug extension and config for CLI (server-name is `pipelines-cli`)
- Composer script descriptions
- More pipeline example YAML files
### Change
- Testsuite for PHP 8 changes
- Updated [documentation about working offline][Offline-PL]
### Fix
- Unintended object to array conversion, supports PHP 8
- Done message for `--validate` saying "verify done" instead of
  "validate done" since [0.0.44](#0044---2020-07-06)
- Detecting readable local streams wrong for non-local remote streams
- Shell test for artifacts, missing in [0.0.50]

[Dev-Doc]: doc/DEVELOPMENT.md
[Run-Travis-Yml]: https://github.com/marketplace/actions/run-travis-yml

## [0.0.50] - 2020-09-14
### Add
- [Documentation about working offline][Offline-PL] incl. pipelines
  example yaml files
- Shell test for artifacts
### Change
- Updated readme
### Fix
- Artifacts. Broken since [0.0.43](#0043---2020-07-05)

[Offline-PL]: doc/PIPELINES-OFFLINE.md

## [0.0.49] - 2020-08-10
### Add
- `--show` step caches
- step caches validation against cache definitions (broken name,
  undefined custom cache) when parsing step caches (`--show`, running a
  pipeline step etc.).
- php cs-fixer custom fixers (thanks [Kuba Werlos])
### Change
- improve [pipeline cache docs](doc/PIPELINES-CACHES.md)

[Kuba Werlos]: https://github.com/kubawerlos

## [0.0.48] - 2020-07-31
### Add
- dependency caches and `--no-cache` for previous behavior, [caches
  documentation](doc/PIPELINES-CACHES.md)
### Fix
- Integration test polluting `$HOME` for docker client stub

## [0.0.47] - 2020-07-23
### Add
- version info to `--debug` above the stacktrace/s (thanks [Andreas Sundqvist])

[Andreas Sundqvist]: https://github.com/sunkan

## [0.0.46] - 2020-07-10
### Fix
- Regression of missing labels for step containers since [0.0.43]

## [0.0.45] - 2020-07-09
### Add
- `step.clone-path` configuration parameter for the path inside the step
  container to deploy the project files, defaults to `/app`
- Schema for new `<pipeline>.step.condition` directives Jun 2020 ([Peter Plewa])
- Schema for new `clone` and `<pipeline>.step.clone` options Feb/Apr 2020
  ([Antoine Büsch])

[Antoine Büsch]: https://bitbucket.org/blog/author/abusch
[Peter Plewa]: https://bitbucket.org/blog/author/pplewa

## [0.0.44] - 2020-07-06
### Add
- `--validate[=<path>]` option to schema-validate a `bitbucket-pipelines.yml`;
  can be used multiple times; validates and exists, non-zero if one or
  multiple files do not validate

## [0.0.43] - 2020-07-05
### Add
- Labels for step and service containers
### Change
- Re-arrange help section for more specific runner options
### Fix
- Regression identifying pipelines service container (`--docker-list` etc.)
  since [0.0.42](#0042---2020-06-25)

## [0.0.42] - 2020-06-25
### Add
- `script.exit-early` bool configuration parameter to exit early in step
  scripts on error more strictly. defaults to false.
- `--ssh` option to mount `$SSH_AUTH_SOCK` into the pipeline/step container,
  SSH agent forwarding (#6)
- `--user[=<uid>:<gid>]` option to run pipeline/step container as current
  or specific user (and group) (#6)
### Change
- Improved container names, service containers names start with
  `pipelines.<service>` instead of `pipelines-<service>`.

## [0.0.41] - 2020-06-21
### Add
- Add `-c <name>=<value>` option to pass a configuration parameter to the
  command.
- Support for `BITBUCKET_STEP_RUN_NUMBER` environment parameter: defaults
  to `1` and set to `1` after first successful step.

## [0.0.40] - 2020-06-17
### Fix
- Wording of pipe scripts comments
- Tmp cleanup in tests
- Parsing a pipe in after-script
- Parsing a pipeline with variables keyword

## [0.0.39] - 2020-06-02
### Add
- Service documentation
- `--service <service>` run `<service>` attached  for trouble  shooting a
   service configuration or watch logs while the service is running
- `--file` accepts the special file-name `-` to read pipelines from standard
  input
### Fix
- Parsing empty step

## [0.0.38] - 2020-06-01
### Add
- `--show` each step and step services
- `--images` shows service images
- `--show-pipelines` for old `--show` format/ behaviour
- `--show-services` to show services in use of pipeline steps

### Change
- Improve parse error reporting of variables in
  service definitions

## [0.0.37] - 2020-05-30
### Add
- Pipeline services other than docker (redis,
  mysql, ...)
### Fix
- Comment formatting in `.env.dist` (minor)

## [0.0.36] - 2020-05-28
### Add
- Help section w/ help message from src in readme
### Fix
- Pipeline default variables command line arguments parsing
- Help message on trigger destination branch name
- Remove static calls to throw a status exception

## [0.0.35] - 2020-05-24
### Change
- Updated readme for instructions
### Fix
- Shell test runner run on invalid test-case/driver
- Type-handling in code and dead/superfluous code
- Remove static calls to throw a parse exception
- After-script ignored all script errors, fix is to exit on first error
- Remove outdated docker client package `basename` property from test
  harness
- Add Apache-2.0 license text (in doc)
- Markdown documentation file typos, wording and structure
- Docker name tag syntax (in doc)
- Change log two release links

## [0.0.34] - 2020-05-13
### Fix
- Show and fake run pipelines with a pipe (#5)

## [0.0.33] - 2020-04-26
### Change
- Readme for container re-use
### Fix
- Build for Phpunit 7.5+
- Diverse lower-level code issues

## [0.0.32] - 2020-04-11
### Add
- Travis PHP 7.4 build
- Composer script `phpunit` for use in diverse test scripts using the
  same configuration
- Composer scripts `which` and `which-php` to obtain the path to composer
  and php in use
### Change
- Test-case forward compatibility for Phpunit 8 (for PHP 7.4 build)
### Fix
- Deprecation warning when running pipelines w/ PHP 7.4 w/o the yaml
  extension
- Code coverage w/ PHP 7.4 / Xdebug 2.9.3
- Composer script `ci` to run pipelines by build PHP version
- Shell test runner usage information
- Travis build configuration validation fixes
- Do not hide errors in Phpunit test-suite

## [0.0.31] - 2020-04-06
### Fix
- Patch fstat permission bits after PHP bug #79082 & #77022 fix to restore
  reproducible phar build
- Correct missing link in Change Log

## [0.0.30] - 2020-04-05
### Add
- Support for `after-script:` incl. `BITBUCKET_EXIT_CODE` environment parameter.
### Fix
- Corrections in Read Me and Change Log

## [0.0.29] - 2020-04-04
### Change
- Pin composer version in phar build; show which composer version in use
- Add destination to pull request --trigger pr:<source>:<destination>

## [0.0.28] - 2020-03-16
### Change
- Phar build: Do not require platform dependencies for composer install
- Travis build: Handle GPG key import before install completely
### Fix
- Tainted phar build on Travis (adding "+" to versions in error), since
  [0.0.25](#0025---2019-12-30)

## [0.0.27] - 2020-03-15
### Add
- `--no-manual` option to not stop at manual step(s). The new default is
  to stop at steps marked manual `trigger: manual`. The first step of a
  pipeline can not be manual, and the first step executed with `--steps`
  will never stop even if it has a `trigger: manual`.
### Fix
- Base unit-test-case missing shim createConfiguredMock method

## [0.0.26] - 2020-03-09
### Add
- `--steps` option to specify which step(s) of a pipeline to run. Same as
  `--step` and to reserve both. `1` as well as `1,2,3`, `1-3` or `-2,3-`
  are valid
### Fix
- Base unit-test-case exception expectation optional message parameter
- Travis build pulling php:5.3
- Read Me and Change Log fixes for links, WS fixes and typo for other
  documentation files (.md, comment in .sh)
- Travis build w/ peer verification issues in composer (disabling
  HTTPS/TLS in custom/unit-tests-php-5.3 pipeline)
- Patch fstat permission bits after PHP bug #79082 fix to restore
  reproducible phar build

## [0.0.25] - 2019-12-30
### Add
- Support of Docker in rootless mode and a How-To in the docs folder.
- Support of `DOCKER_HOST` parameter for `unix://` sockets (Docker Service)
- `--docker-client-pkgs` option to list available docker client binary
  packages (Docker Service)
- `--docker-client` option to specify which docker client binary
  to use (Docker Service)
- Docker Service in YAML injects Linux X86_64 docker client
  binary (Docker Service)
### Change
- Internal improvements of the step runner

## [0.0.24] - 2019-12-21
### Add
- `PIPELINES_PROJECT_PATH` parameter
### Change
- More readable step scripts
### Fix
- Pipelines w/ `--deploy mount` inside a pipeline of `--deploy copy`,
  the current default.
- Busybox on Atlassian Bitbucket Cloud

## [0.0.23] - 2019-12-17
### Change
- Improve `--help` display
- Show scripts with `-v` and drop temporary files for scripts
### Fix
- Exec tester unintentionally override of phpunit test case
  results

## [0.0.22] - 2019-10-12
### Change
- Use Symfony YAML as fall-back parser, replaces
  Mustangostang Spyc (#4)

## [0.0.21] - 2019-09-23
### Fix
- Unintended output of "\x1D" on some container systems

## [0.0.20] - 2019-09-20
### Add
- File format support to check if a step has services
- Test case base class fall-back to Phpunit create* mock
  functions
### Change
- Execute script as a single script instead of executing line by
  line
### Fix
- Container exited while running script (136, broken pipe on socket etc.)
- Remove PHP internal variables like $argv from the environment
  variable maps in containers

## [0.0.19] - 2019-04-02
### Add
- Suggestion to install the PHP YAML extension
- Kept containers are automatically re-used if they still exist
- Support for pull request pipelines
### Change
- Reduce artifact chunk size from fixed number 1792 to string
  length based
### Fix
- Patch fstat permission bits after PHP bug #77022 fix to restore
  reproducible phar build

## [0.0.18] - 2018-08-07
### Add
- Add `--docker-zap` flag kill and clean all pipeline docker
  containers at once
- Fallback for readable file check for systems w/ ACLs where a
  file is not readable by permission but can be read (#1)
### Change
- Pipeline step specific container names instead of random UUIDs
  so that keeping pipelines (and only if in mind) makes this all
  much more predictable

## [0.0.17] - 2018-05-29
### Change
- Reduce artifact chunk size from 2048 to 1792
### Fix
- Symbolic links in artifacts
- Read me file has some errors and inconsistencies. Again.

## [0.0.16] - 2018-05-04
### Add
- Support for PHP YAML extension, is preferred over Spyc lib if
  available; highly recommended
### Fix
  - All uppercase hexits in builder phar info

## [0.0.15] - 2018-04-23
### Add
- Add `--no-dot-env-files` and `--no-dot-env-dot-dist` flags to
  not pass `.env.dist` and `.env` files to docker as
  `--env-file` arguments

## [0.0.14] - 2018-04-18
### Add
- Tag script to make releases
### Change
- More useful default BITBUCKET_REPO_SLUG value
### Fix
- Coverage checker script precision
- Duplicate output of non-zero exit code information

## [0.0.13] - 2018-03-20
### Fix
- Fix `--error-keep` keeping containers

## [0.0.12] - 2018-03-19
### Add
- Utility status exception
### Change
- Streamline of file parse error handling
- Streamline of utility option and argument errors
- Parsing of utility options and arguments in run routine
### Fix
- Code coverage for unit tests

## [0.0.11] - 2018-03-13
### Add
- Keep container on error option: `--error-keep`
### Change
- Do not keep containers by default, not even on error
### Fix
- Code style

## [0.0.10] - 2018-03-12
### Add
- Coverage check
### Change
- Code style
- Readme for corrections and coverage
### Fix
- Resolution of environment variables (esp. w/ numbers in name)

## [0.0.9] - 2018-02-28
### Add
- Traverse upwards for pipelines file
### Fix
- Phive release signing
- App coverage for deploy copy mode

## [0.0.8] - 2018-02-27
### Add
- Phive release signing
### Fix
- Hardencoded /tmp directory

## [0.0.7] - 2018-02-27
### Fix
- Describe missing `--trigger` in help text
- Build directory owner and attributes for deploy copy mode
- Do not capture artifacts files after failed step

## [0.0.6] - 2018-02-14
### Add
- Support for .env / .env.dist file(s)
- Support for Docker Hub private repositories incl. providing
  credentials via `--env` or `--env-file` environment variables
### Change
- Readme for corrections and coverage
### Fix
- Support for large number of artifacts files
- Crash with image `run-as-user` property in pipelines file
- Deploy copy mode fail-safe against copying errors (e.g.
  permission denied on a file to copy)

## [0.0.5] - 2018-01-29
### Add
- Docker environment variables options: `-e`, `--env` for
  variables and `--env-file` for files
- Composer "ci" script to integrate continuously
- `--no-keep` option to never keep containers, even on error
### Change
- Default `--deploy` mode is now `copy`, was `mount` previously
### Fix
- Image name validation
- Image as a section
- Show same image name only once
- Remove version output from -v, --verbose
- Validation of `--basename` actually being a basename
- Error messages now show the utility name

## [0.0.4] - 2018-01-16
### Add
- Release phar files on Github
### Change
- Various code style improvements
- Readme for corrections and coverage

## [0.0.3] - 2018-01-14
### Add
- Keep container on pipeline step failure automatically
- `--verbatim` option to only output from pipeline, not pipelines
### Change
- --help information
- Various code style improvements

## [0.0.2] - 2018-01-11
### Add
- Brace glob pattern in pipelines
- Change log

## [0.0.1] - 2018-01-10
### Add
- Initial release

[0.0.1]: https://github.com/ktomk/pipelines/releases/tag/0.0.1
[0.0.2]: https://github.com/ktomk/pipelines/releases/tag/0.0.2
[0.0.3]: https://github.com/ktomk/pipelines/releases/tag/0.0.3
[0.0.4]: https://github.com/ktomk/pipelines/releases/tag/0.0.4
[0.0.5]: https://github.com/ktomk/pipelines/releases/tag/0.0.5
[0.0.6]: https://github.com/ktomk/pipelines/releases/tag/0.0.6
[0.0.7]: https://github.com/ktomk/pipelines/releases/tag/0.0.7
[0.0.8]: https://github.com/ktomk/pipelines/releases/tag/0.0.8
[0.0.9]: https://github.com/ktomk/pipelines/releases/tag/0.0.9
[0.0.10]: https://github.com/ktomk/pipelines/releases/tag/0.0.10
[0.0.11]: https://github.com/ktomk/pipelines/releases/tag/0.0.11
[0.0.12]: https://github.com/ktomk/pipelines/releases/tag/0.0.12
[0.0.13]: https://github.com/ktomk/pipelines/releases/tag/0.0.13
[0.0.14]: https://github.com/ktomk/pipelines/releases/tag/0.0.14
[0.0.15]: https://github.com/ktomk/pipelines/releases/tag/0.0.15
[0.0.16]: https://github.com/ktomk/pipelines/releases/tag/0.0.16
[0.0.17]: https://github.com/ktomk/pipelines/releases/tag/0.0.17
[0.0.18]: https://github.com/ktomk/pipelines/releases/tag/0.0.18
[0.0.19]: https://github.com/ktomk/pipelines/releases/tag/0.0.19
[0.0.20]: https://github.com/ktomk/pipelines/releases/tag/0.0.20
[0.0.21]: https://github.com/ktomk/pipelines/releases/tag/0.0.21
[0.0.22]: https://github.com/ktomk/pipelines/releases/tag/0.0.22
[0.0.23]: https://github.com/ktomk/pipelines/releases/tag/0.0.23
[0.0.24]: https://github.com/ktomk/pipelines/releases/tag/0.0.24
[0.0.25]: https://github.com/ktomk/pipelines/releases/tag/0.0.25
[0.0.26]: https://github.com/ktomk/pipelines/releases/tag/0.0.26
[0.0.27]: https://github.com/ktomk/pipelines/releases/tag/0.0.27
[0.0.28]: https://github.com/ktomk/pipelines/releases/tag/0.0.28
[0.0.29]: https://github.com/ktomk/pipelines/releases/tag/0.0.29
[0.0.30]: https://github.com/ktomk/pipelines/releases/tag/0.0.30
[0.0.31]: https://github.com/ktomk/pipelines/releases/tag/0.0.31
[0.0.32]: https://github.com/ktomk/pipelines/releases/tag/0.0.32
[0.0.33]: https://github.com/ktomk/pipelines/releases/tag/0.0.33
[0.0.34]: https://github.com/ktomk/pipelines/releases/tag/0.0.34
[0.0.35]: https://github.com/ktomk/pipelines/releases/tag/0.0.35
[0.0.36]: https://github.com/ktomk/pipelines/releases/tag/0.0.36
[0.0.37]: https://github.com/ktomk/pipelines/releases/tag/0.0.37
[0.0.38]: https://github.com/ktomk/pipelines/releases/tag/0.0.38
[0.0.39]: https://github.com/ktomk/pipelines/releases/tag/0.0.39
[0.0.40]: https://github.com/ktomk/pipelines/releases/tag/0.0.40
[0.0.41]: https://github.com/ktomk/pipelines/releases/tag/0.0.41
[0.0.42]: https://github.com/ktomk/pipelines/releases/tag/0.0.42
[0.0.43]: https://github.com/ktomk/pipelines/releases/tag/0.0.43
[0.0.44]: https://github.com/ktomk/pipelines/releases/tag/0.0.44
[0.0.45]: https://github.com/ktomk/pipelines/releases/tag/0.0.45
[0.0.46]: https://github.com/ktomk/pipelines/releases/tag/0.0.46
[0.0.47]: https://github.com/ktomk/pipelines/releases/tag/0.0.47
[0.0.48]: https://github.com/ktomk/pipelines/releases/tag/0.0.48
[0.0.49]: https://github.com/ktomk/pipelines/releases/tag/0.0.49
[0.0.50]: https://github.com/ktomk/pipelines/releases/tag/0.0.50
[0.0.51]: https://github.com/ktomk/pipelines/releases/tag/0.0.51
[0.0.52]: https://github.com/ktomk/pipelines/releases/tag/0.0.52
[0.0.53]: https://github.com/ktomk/pipelines/releases/tag/0.0.53
[0.0.54]: https://github.com/ktomk/pipelines/releases/tag/0.0.54
[0.0.55]: https://github.com/ktomk/pipelines/releases/tag/0.0.55
[0.0.56]: https://github.com/ktomk/pipelines/releases/tag/0.0.56
[0.0.57]: https://github.com/ktomk/pipelines/releases/tag/0.0.57
[unreleased]: https://github.com/ktomk/pipelines
