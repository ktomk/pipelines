<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;

/**
 * handle configuration related options:
 *
 * -c <name>=<value>
 *
 * @see Options
 *
 * @package Ktomk\Pipelines\Utility\Args
 */
class ConfigOptions extends Options implements Runnable
{
    /**
     * @var Args
     */
    private $args;

    /**
     * @var Options
     */
    private $options;

    /**
     * @param Args $args
     * @param null|Options $options
     *
     * @return ConfigOptions
     */
    public static function bind(Args $args, Options $options = null)
    {
        return new self($args, $options ?: Options::create());
    }

    /**
     * @param Args $args
     * @param Options $options
     */
    public function __construct(Args $args, Options $options)
    {
        $this->args = $args;
        $this->options = $options;

        parent::__construct(array('internal'));
    }

    /**
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     *
     * @return Options
     */
    public function run()
    {
        $this->parse($this->args);

        return $this->options;
    }

    /**
     * Parse keep arguments
     *
     * @param Args $args
     *
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     *
     * @return void
     *
     */
    public function parse(Args $args)
    {
        $options = $this->options;
        $booleans = array('true' => true, 'false' => false);

        while (null !== $setting = $args->getOptionArgument('c')) {
            list($name, $value) = explode('=', $setting, 2) + array('undef', null);
            /** @var string $name */
            $default = $options->get($name);
            $result = $options->verify($name, $value);
            if (null === $result) {
                throw new \InvalidArgumentException(sprintf('not a %s: "%s"', $name, $value));
            }
            $type = null === $default ? 'string' : gettype($default);
            'boolean' === $type && $value = isset($booleans[$value]) ? $booleans[$value] : $value;
            settype($value, $type) && $options->definition[$name] = array($value);
        }
    }
}
