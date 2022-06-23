<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value\Env;

/**
 * --env-file <value>
 */
class EnvFile implements \IteratorAggregate, \Countable
{
    /**
     * @var array|EnvVar[]
     */
    private $env = array();

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = (string)$path;
        $this->loadPath($this->path);
    }

    public function __toString()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getPairs()
    {
        $pairs = array();
        foreach ($this->env as $var) {
            $pairs[] = $var->getPair();
        }

        return $pairs;
    }

    public function count()
    {
        return count($this->env);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->env);
    }

    private function loadPath($path)
    {
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines) {
            throw new \InvalidArgumentException(sprintf(
                "File read error: '%s'",
                $path
            ));
        }

        $definitions = preg_grep('~^(\s*#.*|\s*)$~', $lines, PREG_GREP_INVERT);
        if (false === $definitions) {
            // @codeCoverageIgnoreStart
            throw new \UnexpectedValueException(sprintf(
                "Failure getting definitions from file: '%s'",
                $path
            ));
            // @codeCoverageIgnoreEnd
        }

        $this->addEnvVarDefs($definitions);
    }

    private function addEnvVarDefs(array $envVarDefs)
    {
        foreach ($envVarDefs as $index => $envVarDef) {
            try {
                $var = new EnvVar($envVarDef);
            } catch (\InvalidArgumentException $exception) {
                $context = new \ErrorException(
                    $exception->getMessage(),
                    0,
                    1,
                    $this->path,
                    $index + 1,
                    $exception
                );

                throw new \InvalidArgumentException(
                    sprintf('%s:%d %s', $this->path, $index + 1, $exception->getMessage()),
                    0,
                    $context
                );
            }
            $this->env[] = $var;
        }
    }
}
