<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Args\Args;
use Ktomk\Pipelines\Cli\Args\Collector;
use Ktomk\Pipelines\Lib;

/**
 * Pipeline environment collaborator
 */
class Env
{
    /**
     * @var array pipelines (bitbucket) environment variables
     */
    private $vars = array();

    /**
     * collected arguments
     *
     * @var array
     */
    private $collected = array();

    /**
     * environment variables to inherit from
     *
     * @var array
     */
    private $inherit = array();

    /**
     * @var null|EnvResolver
     */
    private $resolver;

    /**
     * @param null|array $inherit
     *
     * @return Env
     */
    public static function create(array $inherit = array())
    {
        $env = new self();
        $env->initDefaultVars($inherit);

        return $env;
    }

    /**
     * Initialize default environment used in a Bitbucket Pipeline
     *
     * As the runner mimics some values, defaults are available
     *
     * @param array $inherit Environment variables to inherit from
     */
    public function initDefaultVars(array $inherit)
    {
        $this->inherit = array_filter($inherit, 'is_string');

        $inheritable = array(
            'BITBUCKET_BOOKMARK' => null,
            'BITBUCKET_BRANCH' => null,
            'BITBUCKET_BUILD_NUMBER' => '0',
            'BITBUCKET_COMMIT' => '0000000000000000000000000000000000000000',
            'BITBUCKET_REPO_OWNER' => '' . Lib::r($inherit['USER'], 'nobody'),
            'BITBUCKET_REPO_SLUG' => 'local-has-no-slug',
            'BITBUCKET_TAG' => null,
            'CI' => 'true',
            'PIPELINES_CONTAINER_NAME' => null,
            'PIPELINES_IDS' => null,
            'PIPELINES_PARENT_CONTAINER_NAME' => null,
            'PIPELINES_PROJECT_PATH' => null,
        );

        $invariant = array(
            'PIPELINES_ID' => null,
        );

        foreach ($inheritable as $name => $value) {
            isset($inherit[$name]) ? $inheritable[$name] = $inherit[$name] : null;
        }

        $var = $invariant + $inheritable;
        ksort($var);

        $this->vars = $var;
    }

    /**
     * Map reference to environment variable setting
     *
     * Only add the BITBUCKET_BOOKMARK/_BRANCH/_TAG variable
     * if not yet set.
     *
     * @param Reference $ref
     */
    public function addReference(Reference $ref)
    {
        if (null === $type = $ref->getType()) {
            return;
        }

        $map = array(
            'bookmark' => 'BITBUCKET_BOOKMARK',
            'branch' => 'BITBUCKET_BRANCH',
            'tag' => 'BITBUCKET_TAG',
            'pr' => 'BITBUCKET_BRANCH',
        );

        if (!isset($map[$type])) {
            throw new \UnexpectedValueException(sprintf('Unknown reference type: "%s"', $type));
        }
        $var = $map[$type];

        if (!isset($this->vars[$var])) {
            $this->vars[$var] = $ref->getName();
        }
    }

    /**
     * @param string $name of container
     */
    public function setContainerName($name)
    {
        if (isset($this->vars['PIPELINES_CONTAINER_NAME'])) {
            $this->vars['PIPELINES_PARENT_CONTAINER_NAME']
                = $this->vars['PIPELINES_CONTAINER_NAME'];
        }

        $this->vars['PIPELINES_CONTAINER_NAME'] = $name;
    }

    /**
     * set the pipelines environment's running pipeline id
     *
     * @param string $id of pipeline, e.g. "default" or "branch/feature/*"
     *
     * @return bool whether was used before (endless pipelines in pipelines loop)
     */
    public function setPipelinesId($id)
    {
        $list = (string)$this->getValue('PIPELINES_IDS');
        $hashes = preg_split('~\s+~', $list, -1, PREG_SPLIT_NO_EMPTY);
        $hashes = array_map('strtolower', /** @scrutinizer ignore-type */ $hashes);

        $idHash = md5($id);
        $hasId = in_array($idHash, $hashes, true);
        $hashes[] = $idHash;

        $this->vars['PIPELINES_ID'] = $id;
        $this->vars['PIPELINES_IDS'] = implode(' ', $hashes);

        return $hasId;
    }

    /**
     * set PIPELINES_PROJECT_PATH
     *
     * can never be overwritten, must be set by pipelines itself for the
     * initial pipeline. will be taken over into each sub-pipeline.
     *
     * @param string $path absolute path to the project directory (deploy source path)
     */
    public function setPipelinesProjectPath($path)
    {
        // TODO $path must be absolute

        if (isset($this->vars['PIPELINES_PROJECT_PATH'])
            || !isset($this->vars['PIPELINES_ID'], $this->vars['PIPELINES_IDS'])
            || $this->vars['PIPELINES_IDS'] !== md5($this->vars['PIPELINES_ID'])
        ) {
            return;
        }

        $this->vars['PIPELINES_PROJECT_PATH'] = $path;
    }

    /**
     * @param null|string $default [optional]
     *
     * @return null|string
     */
    public function getPipelinesProjectPath($default = null)
    {
        if (isset($this->vars['PIPELINES_PROJECT_PATH'])) {
            return $this->vars['PIPELINES_PROJECT_PATH'];
        }

        return $default;
    }

    /**
     * @param string $option "-e" typically for Docker binary
     *
     * @return array of options (from $option) and values, ['-e', 'val1', '-e', 'val2', ...]
     */
    public function getArgs($option)
    {
        $args = $this->collected;

        foreach ($this->getVarDefinitions() as $definition) {
            $args[] = $option;
            $args[] = $definition;
        }

        return $args;
    }

    /**
     * get a variables' value from the inherited
     * environment or null if not set
     *
     * @param $name
     *
     * @return null|string
     */
    public function getInheritValue($name)
    {
        return isset($this->inherit[$name])
            ? $this->inherit[$name]
            : null;
    }

    /**
     * get a variables value or null if not set
     *
     * @param string $name
     *
     * @return null|string
     */
    public function getValue($name)
    {
        return isset($this->vars[$name])
            ? $this->vars[$name]
            : null;
    }

    /**
     * collect option arguments
     *
     * those options to be passed to docker client, normally -e,
     * --env and --env-file.
     *
     * @param Args $args
     * @param string|string[] $option
     *
     * @throws \InvalidArgumentException
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     */
    public function collect(Args $args, $option)
    {
        $collector = new Collector($args);
        $collector->collect($option);
        $this->collected = array_merge($this->collected, $collector->getArgs());

        $this->getResolver()->addArguments($collector);
    }

    /**
     * @param array $paths
     *
     * @throws \InvalidArgumentException
     */
    public function collectFiles(array $paths)
    {
        $resolver = $this->getResolver();
        foreach ($paths as $path) {
            if ($resolver->addFileIfExists($path)) {
                $this->collected[] = '--env-file';
                $this->collected[] = $path;
            }
        }
    }

    /**
     * @return EnvResolver
     */
    public function getResolver()
    {
        if (null === $this->resolver) {
            $this->resolver = new EnvResolver($this->inherit);
        }

        return $this->resolver;
    }

    /**
     * @return array w/ a string variable definition (name=value) per value
     */
    private function getVarDefinitions()
    {
        $array = array();

        foreach ($this->vars as $name => $value) {
            if (isset($value)) {
                $array[] = sprintf('%s=%s', $name, $value);
            }
        }

        return $array;
    }
}
