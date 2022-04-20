<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Composer "which" script host
 *
 * Utility class to provide composer script bindings directly
 * in PHP.
 *
 * the original "echo ${COMPOSER_BINARY}" was not portable
 * for composer 2 < 2.0.7.
 *
 * @package Ktomk\Pipelines
 */
class Composer
{
    /**
     * composer which script
     */
    public static function which()
    {
        echo realpath($_SERVER['argv'][0]), "\n";
    }

    /**
     * @see composer/composer: \Composer\EventDispatcher\EventDispatcher::doDispatch
     */
    public static function whichPhp()
    {
        $phpPath = null;
        if (class_exists('Symfony\Component\Process\PhpExecutableFinder')) {
            $finder = new PhpExecutableFinder();
            $phpPath = $finder->find(false);
        }
        isset($phpPath) || (defined('PHP_BINARY') && $phpPath = constant('PHP_BINARY'));
        $phpEnv = getenv('PHP_BINARY') ?: rtrim(shell_exec("which php 2>/dev/null"));
        empty($phpPath) && $phpPath = $phpEnv;
        echo $phpPath, "\n";
    }
}
