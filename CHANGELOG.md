# Change Log
All notable changes to Pipelines will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [0.0.32] - 2020-04-11
### Added
- Travis PHP 7.4 build
- Composer script `phpunit` for use in diverse test scripts using the
  same configuration
- Composer scripts `which` and `which-php` to obtain the path to composer
  and php in use
### Changed
- Test-case forward compatibility for Phpunit 8 (for PHP 7.4 build)
### Fixed
- Deprecation warning when running pipelines w/ PHP 7.4 w/o the yaml
  extension
- Code coverage w/ PHP 7.4 / Xdebug 2.9.3
- Composer script `ci` to run pipelines by build PHP version
- Shell test runner usage information
- Travis build configuration validation fixes
- Do not hide errors in Phpunit test-suite

## [0.0.31] - 2020-04-06
### Fixed
- Patch fstat permission bits after PHP bug #79082 & #77022 fix to restore
  reproducible phar build
- Correct missing link in Change Log

## [0.0.30] - 2020-04-05
### Added
- Support for `after-script:` incl. `BITBUCKET_EXIT_CODE` environment parameter.
### Fixed
- Corrections in Read Me and Change Log

## [0.0.29] - 2020-04-04
### Changed
- Pin composer version in phar build; show which composer version in use
- Add destination to pull request --trigger pr:<source>:<destination>

## [0.0.28] - 2020-03-16
### Changed
- Phar build: Do not require platform dependencies for composer install
- Travis build: Handle GPG key import before install completely
### Fixed
- Tainted phar build on Travis since 0.0.25 (adding "+" to versions in
  error)

## [0.0.27] - 2020-03-15
### Added
- `--no-manual` option to not stop at manual step(s). The new default is
  to stop at steps marked manual `trigger: manual`. The first step of a
  pipeline can not be manual, and the first step executed with `--steps`
  will never stop even if it has a `trigger: manual`.
### Fixed
- Base unit-test-case missing shim createConfguredMock method

## [0.0.26] - 2020-03-09
### Added
- `--steps` option to specify which step(s) of a pipeline to run. Same as
  `--step` and to reserve both. `1` as well as `1,2,3`, `1-3` or `-2,3-`
  are valid
### Fixed
- Base unit-test-case exception expectation optional message parameter
- Travis build pulling php:5.3
- Read Me and Change Log fixes for links, WS fixes and typo for other
  documentation files (.md, comment in .sh)
- Travis build w/ peer verification issues in composer (disabling
  HTTPS/TLS in custom/unit-tests-php-5.3 pipeline)
- Patch fstat permission bits after PHP bug #79082 fix to restore
  reproducible phar build

## [0.0.25] - 2019-12-30
### Added
- Support of Docker in rootless mode and a How-To in the docs folder.
- Support of `DOCKER_HOST` parameter for `unix://` sockets (Docker Service)
- `--docker-client-pkgs` option to list available docker client binary
  packages (Docker Service)
- `--docker-client` option to specify which docker client binary
  to use (Docker Service)
- Docker Service in YAML injects Linux X86_64 docker client
  binary (Docker Service)
### Changed
- Internal improvements of the step runner

## [0.0.24] - 2019-12-21
### Added
- `PIPELINES_PROJECT_PATH` parameter
### Changed
- More readable step scripts
### Fixed
- Pipelines w/ `--deploy mount` inside a pipeline of `--deploy copy`,
  the current default.
- Busybox on Atlassian Bitbucket Cloud

## [0.0.23] - 2019-12-17
### Changed
- Improve `--help` display
- Show scripts with `-v` and drop temporary files for scripts
### Fixed
- Exec tester unintentionally override of phpunit test case
  results

## [0.0.22] - 2019-10-12
### Changed
- Use Symfony YAML as fall-back parser, replaces
  Mustangostang Spyc (#4)

## [0.0.21] - 2019-09-23
### Fixed
- Unintended output of "\x1D" on some container systems

## [0.0.20] - 2019-09-20
### Added
- File format support to check if a step has services
- Test case base class fall-back to Phpunit create* mock
  functions
### Changed
- Execute script as a single script instead of executing line by
  line
### Fixed
- Container exited while running script (136, broken pipe on socket etc.)
- Remove PHP internal variables like $argv from the environment
  variable maps in containers

## [0.0.19] - 2019-04-02
### Added
- Suggestion to install the PHP YAML extension
- Kept containers are automatically re-used if they still exist
- Support for pull request pipelines
### Changed
- Reduce artifact chunk size from fixed number 1792 to string
  length based
### Fixed
- Patch fstat permission bits after PHP bug #77022 fix to restore
  reproducible phar build

## [0.0.18] - 2018-08-07
### Added
- Add `--docker-zap` flag kill and clean all pipeline docker
  containers at once
- Fallback for readable file check for sytems w/ ACLs where a
  file is not readable by permission but can be read (#1)
### Changed
- Pipeline step specific container names instead of random UUIDs
  so that keeping pipelines (and only if in mind) makes this all
  much more predictable

## [0.0.17] - 2018-05-29
### Changed
- Reduce artifact chunk size from 2048 to 1792
### Fixed
- Symbolic links in artifacts
- Read me file has some errors and inconsistencies. Again.

## [0.0.16] - 2018-05-04
### Added
- Support for PHP YAML extension, is preferred over Spyc lib if
  available; highly recommended
### Fixed
  - All uppercase hexits in builder phar info

## [0.0.15] - 2018-04-23
### Added
- Add `--no-dot-env-files` and `--no-dot-env-dot-dist` flags to
  not pass `.env.dist` and `.env` files to docker as
  `--env-file` arguments

## [0.0.14] - 2018-04-18
### Added
- Tag script to make releases
### Changed
- More useful default BITBUCKET_REPO_SLUG value
### Fixed
- Coverage checker script precision
- Duplicate output of non-zero exit code information

## [0.0.13] - 2018-03-20
### Fixed
- Fix `--error-keep` keeping containers

## [0.0.12] - 2018-03-19
### Added
- Utility status exception
### Changed
- Streamline of file parse error handling
- Streamline of utility option and argument errors
- Parsing of utility options and arguments in run routine
### Fixed
- Code coverage for unit tests

## [0.0.11] - 2018-03-13
### Added
- Keep container on error option: `--error-keep`
### Changed
- Do not keep containers by default, not even on error
### Fixed
- Code style

## [0.0.10] - 2018-03-12
### Added
- Coverage check
### Changed
- Code style
- Readme for corrections and coverage
### Fixed
- Resolution of environment variables (esp. w/ numbers in name)

## [0.0.9] - 2018-02-28
### Added
- Traverse upwards for pipelines file
### Fixed
- Phive release signing
- App coverage for deploy copy mode

## [0.0.8] - 2018-02-27
### Added
- Phive release signing
### Fixed
- Hardencoded /tmp directory

## [0.0.7] - 2018-02-27
### Fixed
- Describe missing `--trigger` in help text
- Build directory owner and attributes for deploy copy mode
- Do not capture artifacts files after failed step

## [0.0.6] - 2018-02-14
### Added
- Support for .env / .env.dist file(s)
- Support for Docker Hub private repositories incl. providing
  credentials via `--env` or `--env-file` environment variables
### Changed
- Readme for corrections and coverage
### Fixed
- Support for large number of artifacts files
- Crash with image `run-as-user` property in pipelines file
- Deploy copy mode fail-safe against copying errors (e.g.
  permission denied on a file to copy)

## [0.0.5] - 2018-01-29
### Added
- Docker environment variables options: `-e`, `--env` for
  variables and `--env-file` for files
- Composer "ci" script to integrate continuously
- `--no-keep` option to never keep containers, even on error
### Changed
- Default `--deploy` mode is now `copy`, was `mount` previously
### Fixed
- Image name validation
- Image as a section
- Show same image name only once
- Remove version output from -v, --verbose
- Validation of `--basename` actually being a basename
- Error messages now show the utility name

## [0.0.4] - 2018-01-16
### Added
- Release phar files on Github
### Changed
- Various code style improvements
- Readme for corrections and coverage

## [0.0.3] - 2018-01-14
### Added
- Keep container on pipeline step failure automatically
- `--verbatim` option to only output from pipeline, not pipelines
### Changed
- --help information
- Various code style improvements

## [0.0.2] - 2018-01-11
### Added
- Brace glob pattern in pipelines
- Change log

## [0.0.1] - 2018-01-10
### Added
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
[unreleased]: https://github.com/ktomk/pipelines
