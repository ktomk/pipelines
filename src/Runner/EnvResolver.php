<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args\Args;
use Ktomk\Pipelines\Cli\Args\OptionFilterIterator;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\Value\Env\EnvFile;
use Ktomk\Pipelines\Value\Env\EnvVar;
use UnexpectedValueException;

/**
 * resolve environment variables against docker --env & --env-file arguments
 *
 * some string values in the bitbucket-pipelines.yml file might need resolution
 * against a part of the current host environment but only if set for the
 * container as well (--env-file, -e, --env)
 *
 * @package Ktomk\Pipelines\Runner\Runner
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
     * @param string|string[] $stringOrArray
     *
     * @throws UnexpectedValueException
     *
     * @return string|string[]
     *
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
     *
     * @throws InvalidArgumentException
     *
     * @return void
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
     * @param string $path path to file
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public function addFile($path)
    {
        foreach (new EnvFile($path) as $var) {
            $this->addVar($var);
        }
    }

    /**
     * add a file but only if it exists (similar to --env-file option)
     *
     * @param string $file path to (potentially existing) file
     *
     * @throws InvalidArgumentException
     *
     * @return bool file was added
     */
    public function addFileIfExists($file)
    {
        if (!LibFs::isReadableFile($file)) {
            return false;
        }

        $this->addFile($file);

        return true;
    }

    /**
     * add a variable definition (-e, --env <definition>)
     *
     * @param string $definition variable definition, either name only or w/ equal sign
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public function addDefinition($definition)
    {
        $this->addVar(new EnvVar($definition));
    }

    /**
     * @param EnvVar $var
     *
     * @return void
     */
    public function addVar(EnvVar $var)
    {
        list($name, $value) = $var->getPair();

        if (null === $value && isset($this->environment[$name])) {
            $value = $this->environment[$name];
        }

        $this->variables[$name] = $value;
    }

    /**
     * get value of variable
     *
     * @param string $name of variable to obtain value from
     *
     * @return null|string value, null if unset
     */
    public function getValue($name)
    {
        return isset($this->variables[$name])
            ? $this->variables[$name]
            : null;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * replace variable with its content if it is a portable, Shell and
     * Utilities variable name (see POSIX).
     *
     * zero-length string if the variable is undefined in the resolver
     * context.
     *
     * @param string $string
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    public function resolveString($string)
    {
        $pattern = '~^\$([A-Z_]+[0-9A-Z_]*)$~';
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
