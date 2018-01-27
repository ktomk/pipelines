<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args\Args;
use Ktomk\Pipelines\Cli\Args\OptionFilterIterator;

/**
 * resolve environment variables against docker --env & --env-file arguments
 *
 * some string values in the bitbucket-pipelines.yml file might need resolution
 * against a part of the current host environment but only if set for the
 * container as well (--env-file, -e, --env)
 *
 * @package Ktomk\Pipelines\Runner
 */
class EnvResolver
{

    /**
     * @var array host environment (that exports)
     */
    private $environment;


    /**
     * @var array container environment (partial w/o bitbucket environment)
     */
    private $variables;

    /**
     * EnvResolver constructor.
     * @param array|string[] $environment host environment variables (strings)
     */
    public function __construct(array $environment)
    {
        $this->environment = array_filter($environment, 'is_string');
    }

    public function addArguments(Args $args)
    {
        $files = new OptionFilterIterator($args, 'env-file');
        foreach ($files->getArguments() as $file) {
            $this->addFile($file);
        }

        $definitions = new OptionFilterIterator($args, array('e', 'env'));
        foreach ($definitions->getArguments() as $definition) {
            $this->addDefinition($definition);
        }
    }

    /**
     * add a file (--env-file option)
     *
     * @param string $file path to file
     */
    public function addFile($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines) {
            throw new InvalidArgumentException(sprintf(
                    "File read error: '%s'", $file
                )
            );
        }
        $definitions = preg_grep('~^(\s*#.*|\s*)$~', $lines, PREG_GREP_INVERT);
        foreach ($definitions as $definition) {
            $this->addDefinition($definition);
        }
    }

    /**
     * add a variable definition (-e, --env option)
     *
     * @param string $definition variable definition, either name only or w/ equal sign
     */
    public function addDefinition($definition)
    {
        $pattern = '~^([^$={}\\x0-\\x20\\x7f-\\xff-]+)(?:=(.*))?$~';

        $result = preg_match($pattern, $definition, $matches);
        if (0 === $result) {
            throw new InvalidArgumentException(sprintf(
                "Variable definition error: '%s'", $definition
            ));
        }

        list(, $name, $value) = $matches + array(2 => null);

        if (null === $value && isset($this->environment[$name])) {
            $value = $this->environment[$name];
        }

        $this->variables[$name] = $value;
    }

    /**
     * get value of variable
     *
     * @param string $name of variable to obtain value from
     * @return string|null value, null if unset
     */
    public function getValue($name)
    {
        return isset($this->variables[$name])
            ? $this->variables[$name]
            : null;
    }
}
