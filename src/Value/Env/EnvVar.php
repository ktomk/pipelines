<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value\Env;

/**
 * -e, --env <value>
 */
class EnvVar
{
    const PATTERN = '~^([^\\x0-\\x20$={}\\x7f-\\xff-]+)(?:=([^\\0]*))?$~';

    /**
     * @var array
     */
    private $expression;

    /**
     * @param string $env
     */
    public function __construct($env)
    {
        $this->parseDefinition((string)$env);
    }

    public function __toString()
    {
        list($name, $value) = $this->expression;

        return $name . (isset($value) ? '=' . $value : '');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->expression[0];
    }

    /**
     * @return null|string
     */
    public function tryValue()
    {
        return $this->expression[1];
    }

    public function getValue()
    {
        $value = $this->expression[1];
        if (null === $value) {
            throw new \BadFunctionCallException(
                sprintf('Environment variable %s has no value', $this->expression[0])
            );
        }

        return $value;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    public function getPair()
    {
        return $this->expression;
    }

    /**
     * @param string $definition
     *
     * @return void
     */
    private function parseDefinition($definition)
    {
        $result = preg_match(self::PATTERN, $definition, $matches);
        if (0 === $result) {
            throw new \InvalidArgumentException(sprintf(
                'Environment variable error: %s',
                addcslashes($definition, "\0..\40\\\177..\377")
            ));
        }

        /** @var array{0: string, 1: string, 2: null|string} $matches */

        list(, $name, $value) = $matches + array(2 => null);

        $this->expression = array($name, $value);
    }
}
