<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Utility\Options;
use Ktomk\Pipelines\Value\Prefix;

/**
 * Runner options parameter object
 */
class RunOpts
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var string
     */
    private $binaryPackage;

    /**
     * @var null|string of step expression or null if not set
     */
    private $steps;

    /**
     * @var bool
     */
    private $noManual = false;

    /**
     * Static factory method
     *
     * @param string $prefix [optional]
     * @param string $binaryPackage [optional] package name or path to binary (string)
     * @param Options $options [optional]
     *
     * @return RunOpts
     */
    public static function create($prefix = null, $binaryPackage = null, Options $options = null)
    {
        null === $options && $options = Options::create();

        return new self($prefix, $options, $binaryPackage);
    }

    /**
     * RunOpts constructor.
     *
     * NOTE: All run options are optional by design (pass NULL).
     *
     * @param string $prefix
     * @param null|Options $options
     * @param string $binaryPackage package name or path to binary (string)
     */
    public function __construct($prefix = null, Options $options = null, $binaryPackage = null)
    {
        $this->prefix = null === $prefix ? $prefix : Prefix::verify($prefix);
        $this->options = $options;
        $this->binaryPackage = $binaryPackage;
    }

    /**
     * @param string $prefix
     *
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * The prefix is used when creating containers for the container name.
     *
     * See --prefix option.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $name
     *
     * @return null|string
     */
    public function getOption($name)
    {
        if (!isset($this->options)) {
            return null;
        }

        return $this->options->get($name);
    }

    /**
     * @param string $binaryPackage
     *
     * @return void
     */
    public function setBinaryPackage($binaryPackage)
    {
        $this->binaryPackage = $binaryPackage;
    }

    /**
     * @return string
     */
    public function getBinaryPackage()
    {
        return $this->binaryPackage;
    }

    /**
     * @return null|string
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param null|string $steps
     *
     * @return void
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;
    }

    /**
     * @return bool
     */
    public function isNoManual()
    {
        return $this->noManual;
    }

    /**
     * @param bool $noManual
     *
     * @return void
     */
    public function setNoManual($noManual)
    {
        $this->noManual = (bool)$noManual;
    }
}
