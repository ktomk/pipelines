--TEST--
check bootstrap path w/ missing vendor/autoload.php
--FILE--
<?php
if (file_exists('vendor/autoload.php')) {
    rename('vendor/autoload.php', 'vendor/autoload.php.phpt~');
}

defined('STDERR') || define('STDERR', fopen('php://stderr', 'wb'));

require __DIR__ . '/../../../src/bootstrap.php';
?>
--EXPECT--
To use pipelines set up project dependencies via `composer install` first
See https://getcomposer.org/download/ for how to install Composer
--CLEAN--
<?php
if (file_exists('vendor/autoload.php.phpt~')) {
    rename('vendor/autoload.php.phpt~', 'vendor/autoload.php');
}
?>
