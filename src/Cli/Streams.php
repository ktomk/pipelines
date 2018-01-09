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

    /**
     * Streams constructor.
     *|string
     * handles can be null (noop), a resource (reuse) or a string that
     * is opened before use (currently on creation, could be postponed)
     *
     * @param resource|null|string $in
     * @param resource|null|string $out
     * @param resource|null|string $err
     */
    public function __construct($in = null, $out = null, $err = null)
    {
        $this->handles = array();
        $this->addHandle($in);
        $this->addHandle($out);
        $this->addHandle($err);
    }

    public function __invoke($string)
    {
        $this->out(sprintf("%s\n", $string));
    }

    public function out($string)
    {
        $handle = $this->handles[1][0];
        $handle && fputs($handle, $string);
    }

    public function err($string)
    {
        $handle = $this->handles[2][0];
        $handle && fputs($handle, $string);
    }

    /**
     * @param null|resource|string $context
     */
    private function addHandle($context)
    {
        $new = array(null, null);
        $num = count($this->handles);
        if (null === $context || is_resource($context)) {
            $new[0] = $context;
        } else {
            $new[0] = fopen($context, 0 === $num ? 'r' : 'w');
            $new[1] = $context;
        }

        $this->handles[$num] = $new;
    }

    public function __destruct()
    {
        foreach ($this->handles as $handle => $descriptor) {
            list($resource, $reference) = $descriptor;
            if ($resource && $reference && is_resource($resource)) {
                fclose($resource);
                $this->handles[$handle][0] = null;
            }
        }
    }
}
