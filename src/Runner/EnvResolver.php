<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args\Args;
use Ktomk\Pipelines\Cli\Args\OptionFilterIterator;
use UnexpectedValueException;

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
     *
     * @param array|string[] $environment host environment variables (name => string)
     */
    public function __construct(array $environment)
    {
        $this->environment = array_filter($environment, 'is_string');
    }

    /**
     * resolve a string or an array of strings
     *
     * @param array|string $stringOrArray
     * @throws \UnexpectedValueException
     * @return array|string
     * @see resolveString
     */
    public function __invoke($stringOrArray)
    {
        // TODO(tk): provide full environment (string) on NULL parameter
        if (is_array($stringOrArray)) {
            return array_map(array($this, 'resolveString'), $stringOrArray);
        }

        return $this->resolveString($stringOrArray);
    }

    /**
     * @param Args $args
     * @throws \InvalidArgumentException
     */
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
     * @throws \InvalidArgumentException
     */
    public function addFile($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines) {
            throw new InvalidArgumentException(
                sprintf(
                    "File read error: '%s'",
                    $file
                )
            );
        }
        $this->addLines($lines);
    }

    /**
     * add a file but only if it exists (similar to --env-file option)
     *
     * @see addFile
     * @param string $file path to (potentially existing) file
     * @throws \InvalidArgumentException
     * @return bool file was added
     */
    public function addFileIfExists($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            return false;
        }

        $this->addFile($file);

        return true;
    }

    /**
     * @param array $lines
     * @throws \InvalidArgumentException
     */
    public function addLines(array $lines)
    {
        $definitions = preg_grep('~^(\s*#.*|\s*)$~', $lines, PREG_GREP_INVERT);
        foreach ($definitions as $definition) {
            $this->addDefinition($definition);
        }
    }

    /**
     * add a variable definition (-e, --env option)
     *
     * @param string $definition variable definition, either name only or w/ equal sign
     * @throws \InvalidArgumentException
     */
    public function addDefinition($definition)
    {
        $pattern = '~^([^$={}\\x0-\\x20\\x7f-\\xff-]+)(?:=(.*))?$~';

        $result = preg_match($pattern, $definition, $matches);
        if (0 === $result) {
            throw new InvalidArgumentException(sprintf(
                "Variable definition error: '%s'",
                $definition
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
     * @return null|string value, null if unset
     */
    public function getValue($name)
    {
        return isset($this->variables[$name])
            ? $this->variables[$name]
            : null;
    }

    /**
     * replace variable with its content if it is a portable, Shell and
     * Utilities variable name (see POSIX).
     *
     * zero-length string if the variable is undefined in the resolver
     * context.
     *
     * @param $string
     * @throws \UnexpectedValueException
     * @return string
     */
    public function resolveString($string)
    {
        $pattern = '~^\$([A-Z_]+[0-9A-Z_])*$~';
        $result = preg_match($pattern, $string, $matches);
        if (false === $result) {
            throw new UnexpectedValueException('regex pattern error'); // @codeCoverageIgnore
        }

        if (0 === $result) {
            return $string;
        }

        list(, $name) = $matches;
        $value = $this->getValue($name);

        return (string)$value;
    }
}
