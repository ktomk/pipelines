# Change Log
All notable changes to Pipelines will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [unreleased]
### Fixed
- Duplicate output of non-zero exit code information
### Added
- Tag script to make releases

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
