# Pipelines Utility Development

Pipelines is a command-line utility written in [PHP][PHP] \[PHP] and
developed as a [composer][COMPOSER] \[COMPOSER] package with a [phar
file build][PHAR] \[PHAR].

The currently pinned composer version is `2.1.11`, pinning the version
is mostly important for the phar build as it is not reproducible
otherwise. In general any version of composer is supported.

??? note "Composer 2 Support"
    Composer 2 is the standard in the Pipelines project. Previously it
    was Composer 1.

    Just note that the composer.lock file should be written with the
    pinned composer version.

Currently the `composer.lock` targets a PHP 7.4 system with Phpunit 9
for development. It should be similar for PHP 8.0.

??? note "Xdebug 3 Support"
    [Xdebug 3.0.0][XDEBUG] \[XDEBUG] is supported but _may_ make some
    tests, especially the PHPT tests, fail (segmentation faults). If so
    then check you have got the latest Xdebug 3 version. As a fall-back
    Xdebug 2 can be used for any PHP version below PHP 8.0.
    See [Supported Versions and Compatibility](https://xdebug.org/docs/compat)
    also [for older Xdebug and PHP versions](https://2.xdebug.org/docs/compat)
    .

After checkout run `composer install` to bootstrap and `composer ci`
for the local CI. It will also run pipelines pipelines, so docker is
required, the shell tests require bash. Use `composer run-script --list`
to get a list of all composer scripts.

`composer ci` should run through after checkout and before committing
any changes. while developing, `composer dev` is a good intermediate.

??? tip "PHP Backwards Compatibility"
    Even the pipelines project accepts issues/PRs with pseudo-code,
    when it is about concrete PHP code, the requirement is that it is
    (backwards) compatible with **PHP 5.3.3** (released Jul 2010). This
    is not a common requirement and the `pipelines` project tries to
    offer support even in local development (like running the phpunit
    tests under the minimum version as a custom pipeline). If you use
    [Phpstorm][PHPSTORM] \[PHPSTORM] enable the [PHP 5.3 language
    level](https://www.jetbrains.com/help/phpstorm/php.html) for your
    cloned `pipelines` project. This helps preventing falling into new
    language syntax unnoticed.

    In any case don't fear, this is normally easy to sort-out. Just
    leave a comment in case things didn't work for you out of the box
    and share the PHP version in use.

## Support of Different PHP Versions

While `pipelines` runs and builds on PHP 5.3.3+, the PHP version when developing `pipelines` may differ.

Please see the following table for a break-down based on PHP versions
incl. remarks:

| CAPABILITY   | 5.3 | 5.4 | 5.5 | 5.6 | 7.0 | 7.1 | 7.2 | 7.3 | 7.4 | 8.0 | 8.1 |
|--------------|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|
| composer *1  | X   | X   | X   | X   | X   | X   | X   | X   | X   | X   | X   |
| phpunit (version) | X (4)   | X (4&nbsp;*2) | X (4&nbsp;*2) | X (5)   | X (6)   | X (6)   | X (7)   | X (8)   | X (8/ 9&nbsp;*3) | X (8&nbsp;*2/ 9)   | X (8&nbsp;*2/ 9)   |
| phar-build   | X   | X   | X   | X   | X   | X   | X   | X   | X   | X   | X   |
| php-cs-fixer |     |     |     |     |     |     |     |     | X   | X   | X *4|
| xdebug 3 *5  |     |     |     |     |     |     | X&nbsp;*6| X   | X   | X   | X   |

???+ info "Remarks"
    1. Composer 1 and 2 are supported.
    2. Configuration expected to work but undefined in (local) CI. See
       as well [Phpunit/PHP version compatibility matrix][PHPUNIT]
       \[PHPUNIT], e.g. it is possible to run PHP 8 with Phpunit 8
       (since 30th Nov 2020).
    3. Phpunit 9 is used in the local development package
       (`composer.lock`), remote [Travis CI][TRAVIS-CI] \[TRAVIS-CI] is
       using a Phpunit 8 profile for PHP 7.4.
    4. [PHP-CS-Fixer][PHP-CS-FIXER] is used in `pipelines` to control
       the code style, so relatively central in the CI pipeline.
       Unfortunately, it does (did) not cover all the PHP versions that
       `pipelines` supports. Especially with PHP 8.1 it refuses to install
       with composer. This requires to fake the PHP version (e.g. as
       7.4.99) or to use the `--ignore-platform-reqs` - or more precisely
       `--ignore-platform-req=php` - option of composer to install with
       PHP 8.1.
    5. Xdebug 3 is required for PHP 8 and in tests for all PHP versions
       it supports. It is somewhat new, segfaults may have been spotted in
       CI runs (local, on Scrutinizer). Check the version, and then an upgrade. Pipelines uses Xdebug in development for code-coverage and step-debugging. See
       _Xdebug 3 Support_ above.
    6. Xdebug 3 support for PHP 7.2 is limited to security support. Same
       applies to Xdebug 2.9 for PHP 7.2. No other Xdebug version next
       to 3.0 or 2.9 is supported at all for PHP 7.2. See _Xdebug 3
       Support_ above. Currently the test-suite does not work for PHP
       7.2 and Xdebug 3 running on Phpunit 7.

## PHP Version and Reproducible PHAR Build

The phar build, on a clean checkout, with the pinned composer version
creates the same phar file (signature/checksum) regardless of the php
version in use.

Invoke the phar build with:

```shell
composer build
```

Afterwards find the phar-file as `build/pipelines.phar`.

## Test / Build with Different PHP Versions

The local CI can be run with different php versions by running composer
with a specific PHP version:

~~~
php8.0 -f "$(composer which)" -- ci
~~~
_(use `ci` or any other project composer script command)_

The shell tests and many other scripts when using PHP are respecting the
`PHP_BINARY` environment variable:

~~~
$ PHP_BINARY=php5.6 lib/script/ppconf.sh self-test
ppconf self-test
bash....: /bin/bash                       	GNU bash, version 4.4.20(1)-release (x86_64-pc-linux-gnu)
composer: /home/user/bin/composer          	Composer version 1.10.17 2020-10-30 22:31:58 (/home/user/bin/composer.phar)
find....: /usr/bin/find                   	find (GNU findutils) 4.7.0-git
gpg.....: /usr/bin/gpg                    	gpg (GnuPG) 2.2.4
make....: /usr/bin/make                   	GNU Make 4.1
openssl.: /usr/bin/openssl                	OpenSSL 1.1.1g  21 Apr 2020
os......: NAME="Ubuntu"
os......: VERSION="18.04.5 LTS (Bionic Beaver)"
os......: ID=ubuntu
php.....: /usr/bin/php5.6                 	PHP 5.6.40-38+ubuntu18.04.1+deb.sury.org+1 (cli)  (/usr/bin/php5.6)
python..: /usr/bin/python                 	Python 2.7.17
python3.: /usr/bin/python3                	Python 3.6.9
sed.....: /bin/sed                        	sed (GNU sed) 4.4
sh......: /bin/sh                         	/bin/dash
tar.....: /bin/tar                        	tar (GNU tar) 1.29
unzip...: /usr/bin/unzip                  	UnZip 6.00 of 20 April 2009, by Debian. Original by Info-ZIP.
xdebug..: no
~~~
_(note the given "php" version information)_

## References

* \[COMPOSER]: https://getcomposer.org/
* \[PHAR]: https://www.php.net/phar
* \[PHP]: https://www.php.net/
* \[PHP-CS-FIXER]: https://github.com/FriendsOfPHP/PHP-CS-Fixer
* \[PHPSTORM]: https://www.jetbrains.com/phpstorm/
* \[PHPUNIT]: https://phpunit.de/supported-versions.html
* \[TRAVIS-CI]: https://travis-ci.com/github/ktomk/pipelines
* \[XDEBUG]: https://xdebug.org/

[COMPOSER]: https://getcomposer.org/
[PHAR]: https://www.php.net/phar
[PHP]: https://www.php.net/
[PHP-CS-FIXER]: https://github.com/FriendsOfPHP/PHP-CS-Fixer
[PHPSTORM]: https://www.jetbrains.com/phpstorm/
[PHPUNIT]: https://phpunit.de/supported-versions.html
[TRAVIS-CI]: https://travis-ci.com/github/ktomk/pipelines
[XDEBUG]: https://xdebug.org/
