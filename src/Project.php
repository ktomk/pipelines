<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use InvalidArgumentException;
use Ktomk\Pipelines\Value\Prefix;

/**
 * Class Project
 *
 * Represents the project pipelines is running/operating on
 *
 * @package Ktomk\Pipelines
 */
class Project
{
    /**
     * @var string path to the project directory on user/host system
     */
    private $path;

    /**
     * @var string prefix ('pipelines' by default)
     */
    private $prefix;

    /**
     * Project constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $buffer = LibFsPath::normalize($path);
        if (!basename($buffer)) {
            throw new InvalidArgumentException(sprintf('Invalid project directory: "%s"', $path));
        }
        $this->path = $buffer;
    }

    /**
     *
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return basename($this->path);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = Prefix::verify($prefix);
    }
}
