<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

/**
 * For the standard streams used in the CLI application, output
 * to both standard output and standard error is available in
 * an encapsulated manner.
 *
 * @package Ktomk\Pipelines\Cli
 */
class Streams
{
    /**
     * @var array
     */
    private $handles;

    /**
     * private marker that instance should close handles on dtor
     * @var bool
     */
    private $closeOnDestruct;

    /**
     * Streams constructor.
     *|string
     * handles can be null (noop), a resource (reuse) or a string that
     * is opened before use (currently on creation, could be postponed)
     *
     * @param null|resource|string $in
     * @param null|resource|string $out
     * @param null|resource|string $err
     */
    public function __construct($in = null, $out = null, $err = null)
    {
        $this->handles = array();
        $this->addHandle($in);
        $this->addHandle($out);
        $this->addHandle($err);
    }

    public function __destruct()
    {
        foreach ($this->handles as $handle => $descriptor) {
            list($resource, $context) = $descriptor;
            if ($resource && is_string($context) && is_resource($resource)) {
                fclose($resource);
                $this->handles[$handle][0] = null;
            }
        }
    }

    public function __invoke($string)
    {
        $this->out(sprintf("%s\n", $string));
    }

    /**
     * Create streams from environment (standard streams)
     */
    public static function create()
    {
        // PHP Doc Bug #43283 CLI does not define STDOUT/STDERR with stdin script
        $care = !defined('STDIN');

        $in = $care ? constant('STDIN') : 'php://stdin';
        $out = $care ? constant('STDOUT') : 'php://stdout';
        $err = $care ? constant('STDERR') : 'php://stderr';

        $streams = new self($in, $out, $err);
        $streams->closeOnDestruct = $care;

        return $streams;
    }

    public function out($string)
    {
        $handle = $this->handles[1][0];
        is_resource($handle) && fwrite($handle, $string);
    }

    public function err($string)
    {
        $handle = $this->handles[2][0];
        is_resource($handle) && fwrite($handle, $string);
    }

    /**
     * @param Streams $streams
     * @param int $handle
     */
    public function copyHandle(Streams $streams, $handle)
    {
        $array = $streams->handles[$handle];

        // just in case the resource was opened by streams, remove the
        // association to the path
        if (is_resource($array[0])) {
            $array[1] = null;
        }

        $this->handles[$handle] = $array;
    }

    /**
     * @param null|resource|string $context
     * @throws \RuntimeException
     */
    private function addHandle($context)
    {
        $num = count($this->handles);
        if (null === $context || is_resource($context)) {
            $new = array(
                $context,
                null,
            );
        } else {
            $resource = fopen($context, 0 === $num ? 'r' : 'w');
            if (false === $resource) {
                throw new \RuntimeException(sprintf(
                    "failed to open '%s' for %s",
                    $context,
                    0 === $num ? 'reading' : 'writing'
                ));
            }
            $new = array(
                $resource,
                $context,
            );
        }

        $this->handles[$num] = $new;
    }
}
