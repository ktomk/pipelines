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
     * @var null|Options
     */
    private $options;

    /**
     * @var null|string
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
     * @var null|string --user $(id -u):$(id -g)
     */
    private $user;

    /**
     * @var null|true --ssh-auth
     */
    private $ssh;

    /**
     * Static factory method
     *
     * @param string $prefix [optional]
     * @param null|string $binaryPackage [optional] package name or path to binary (string)
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
     * NOTE: All run options are optional by design (pass NULL), some have defaults
     *       for that, some need to be initialized/set (marked [setter]) before
     *       dedicated use.
     *
     * @param null|string $prefix [optional] null for default, string for prefix
     * @param null|Options $options [optional]
     * @param string $binaryPackage [optional][setter] package name or path to binary (string)
     * @param null|string $user [optional] user option, non-null (string) if set
     * @param null|true $ssh [optional] ssh support for runner, null for none, true for support of ssh agent
     */
    public function __construct(
        $prefix = null,
        Options $options = null,
        $binaryPackage = null,
        $user = null,
        $ssh = null
    ) {
        $this->setPrefix($prefix);
        $this->options = $options;
        $this->binaryPackage = $binaryPackage;
        $this->user = $user;
        $this->ssh = $ssh;
    }

    /**
     * @param string $prefix
     *
     * @return void
     */
    public function setPrefix($prefix = null)
    {
        $this->prefix = Prefix::filter($prefix);
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
     *
     * FIXME(tk): technically the underlying $this->options->get() can also
     *            return bool, this is somewhat not fully implemented and
     *            should perhaps be delegated {@see getBoolOption()}.
     *
     * @param string $name
     *
     * @return string
     */
    public function getOption($name)
    {
        $buffer = $this->getOptionImplementation($name);

        if (is_bool($buffer)) {
            // @codeCoverageIgnoreStart
            throw new \BadMethodCallException(
                sprintf("use bool for option: '%s'", $name)
            );
            // @codeCoverageIgnoreEnd
        }

        return $buffer;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function getBoolOption($name)
    {
        return (bool)$this->getOptionImplementation($name);
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
        if (null === $this->binaryPackage) {
            // @codeCoverageIgnoreStart
            throw new \BadMethodCallException('binary-package not yet initialized');
            // @codeCoverageIgnoreEnd
        }

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

    /**
     * @return null|string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null|string $user
     *
     * @return void
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return null|true
     */
    public function getSsh()
    {
        return $this->ssh ? true : null;
    }

    /**
     * @param null|true $ssh
     *
     * @return void
     */
    public function setSsh($ssh)
    {
        $this->ssh = $ssh ? true : null;
    }

    /**
     * @param string $name
     *
     * @return bool|string
     */
    private function getOptionImplementation($name)
    {
        $options = $this->options;
        if (!isset($options)) {
            throw new \BadMethodCallException('no options');
        }

        $buffer = $options->get($name);
        if (null === $buffer) {
            throw new \InvalidArgumentException(
                sprintf("unknown option: '%s'", $name)
            );
        }

        return $buffer;
    }
}
