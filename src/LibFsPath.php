<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * Class LibFsPath - path utility functions
 *
 * @package Ktomk\Pipelines
 */
class LibFsPath
{
    /**
     * check if path is absolute
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isAbsolute($path)
    {
        // TODO: a variant with PHP stream wrapper prefix support
        /* @see LibFsStream::isUri */

        $count = strspn($path, '/', 0, 3) % 2;

        return (bool)$count;
    }

    /**
     * check if path is basename
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isBasename($path)
    {
        if (in_array($path, array('', '.', '..'), true)) {
            return false;
        }

        if (false !== strpos($path, '/')) {
            return false;
        }

        return true;
    }

    /**
     * Resolve relative path segments in a path on it's own
     *
     * This is not realpath, not resolving any links.
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizeSegments($path)
    {
        if ('' === $path) {
            return $path;
        }

        $buffer = $path;

        $prefix = '';
        $len = strspn($buffer, '/');
        if (0 < $len) {
            $prefix = substr($buffer, 0, $len);
            $buffer = substr($buffer, $len);
        }

        $buffer = rtrim($buffer, '/');

        if (in_array($buffer, array('', '.'), true)) {
            return $prefix;
        }

        $pos = strpos($buffer, '/');
        if (false === $pos) {
            return $prefix . $buffer;
        }

        $buffer = preg_replace('~/+~', '/', $buffer);

        $segments = explode('/', $buffer);
        $stack = array();
        foreach ($segments as $segment) {
            $i = count($stack) - 1;
            if ('.' === $segment) {
                continue;
            }

            if ('..' !== $segment) {
                $stack[] = $segment;

                continue;
            }

            if (($i > -1) && '..' !== $stack[$i]) {
                array_pop($stack);

                continue;
            }

            $stack[] = $segment;
        }

        return $prefix . implode('/', $stack);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public static function containsRelativeSegment($path)
    {
        $segments = array_flip(explode('/', $path));

        return isset($segments['.']) || isset($segments['..']);
    }

    /**
     * Normalize a path as/if common in PHP
     *
     * E.g. w/ phar:// in front which means w/ stream wrappers in
     * mind.
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalize($path)
    {
        $buffer = $path;

        $scheme = '';
        // TODO support for all supported stream wrappers (w/ absolute/relative notation?)
        /* @see LibFsStream::isUri */
        /* @see LibFsPath::isAbsolute */
        if (0 === strpos($buffer, 'phar://') || 0 === strpos($buffer, 'file://')) {
            $scheme = substr($buffer, 0, 7);
            $buffer = substr($buffer, 7);
        }

        $normalized = self::normalizeSegments($buffer);

        return $scheme . $normalized;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public static function isPortable($path)
    {
        return 1 === Preg::match('(^(?>/?(?!-)[A-Za-z0-9._-]+)+$)D', $path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function gateAbsolutePortable($path)
    {
        if (!self::isAbsolute($path)) {
            throw new \InvalidArgumentException(sprintf('not an absolute path: "%s"', $path));
        }

        $normalized = self::normalizeSegments($path);
        if (self::containsRelativeSegment($normalized)) {
            throw new \InvalidArgumentException(sprintf('not a fully qualified path: "%s"', $path));
        }

        if (!self::isPortable($path)) {
            throw new \InvalidArgumentException(sprintf('not a portable path: "%s"', $path));
        }

        return $normalized;
    }
}
