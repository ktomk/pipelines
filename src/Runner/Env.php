<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Args\Args;
use Ktomk\Pipelines\Cli\Args\Collector;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFsPath;
use Ktomk\Pipelines\Runner\Docker\ArgsBuilder;

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
     * @var EnvResolver
     */
    private $resolver;

    /**
     * create with no default initialization
     *
     * @param null|array $inherit
     *
     * @return Env
     */
    public static function create(array $inherit = null)
    {
        $env = new self();
        $env->initInherit((array)$inherit);

        return $env;
    }

    /**
     * @param null|array $inherit
     *
     * @return Env
     */
    public static function createEx(array $inherit = null)
    {
        $env = new self();
        $env->initDefaultVars((array)$inherit);

        return $env;
    }

    /**
     * Initialize default environment used in a Bitbucket Pipeline
     *
     * As the runner mimics some values, defaults are available
     *
     * @param array $inherit Environment variables to inherit from
     *
     * @return void
     */
    public function initDefaultVars(array $inherit)
    {
        $this->initInherit($inherit);

        $inheritable = array(
            'BITBUCKET_BOOKMARK' => null,
            'BITBUCKET_BRANCH' => null,
            'BITBUCKET_BUILD_NUMBER' => '0',
            'BITBUCKET_COMMIT' => '0000000000000000000000000000000000000000',
            'BITBUCKET_REPO_OWNER' => '' . Lib::r($inherit['USER'], 'nobody'),
            'BITBUCKET_REPO_SLUG' => 'local-has-no-slug',
            'BITBUCKET_STEP_RUN_NUMBER' => '1',
            'BITBUCKET_TAG' => null,
            'CI' => 'true',
            'PIPELINES_CONTAINER_NAME' => null,
            'PIPELINES_IDS' => null,
            'PIPELINES_PARENT_CONTAINER_NAME' => null,
            'PIPELINES_PIP_CONTAINER_NAME' => null,
            'PIPELINES_PROJECT_PATH' => null,
        );

        $invariant = array(
            'PIPELINES_ID' => null,
        );

        $var = $invariant + array_intersect_key(array_filter($inherit, 'is_string'), $inheritable) + $inheritable;
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
     *
     * @return void
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

        if ($this->addPrReference($ref)) {
            return;
        }

        $this->addVar($map[$type], $ref->getName());
    }

    /**
     * add a variable if not yet set (real add new)
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function addVar($name, $value)
    {
        if (!isset($this->vars[$name])) {
            $this->vars[$name] = $value;
        }
    }

    /**
     * @param Reference $reference
     *
     * @return bool
     */
    public function addPrReference(Reference $reference)
    {
        if ('pr' !== $reference->getType()) {
            return false;
        }

        $var = array_combine(
            array('BITBUCKET_BRANCH', 'BITBUCKET_PR_DESTINATION_BRANCH'),
            explode(':', $reference->getName(), 2) + array(null, null)
        );

        foreach ($var as $name => $value) {
            isset($value) && $this->addVar($name, $value);
        }

        return true;
    }

    /**
     * @param string $name of container
     *
     * @return void
     */
    public function setContainerName($name)
    {
        if (isset($this->vars['PIPELINES_CONTAINER_NAME'])) {
            $this->vars['PIPELINES_PARENT_CONTAINER_NAME']
                = $this->vars['PIPELINES_CONTAINER_NAME'];
        }

        $this->vars['PIPELINES_CONTAINER_NAME'] = $name;
        $this->setFirstPipelineVariable('PIPELINES_PIP_CONTAINER_NAME', $name);
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
     * $BITBUCKET_STEP_RUN_NUMBER can be inherited from environment and after
     * the first step did run is being reset to 1
     *
     * allows to test it for steps `BITBUCKET_STEP_RUN_NUMBER=2 pipelines --step 1-`
     *
     * @return void
     */
    public function resetStepRunNumber()
    {
        $this->vars['BITBUCKET_STEP_RUN_NUMBER'] = '1';
    }

    /**
     * set PIPELINES_PROJECT_PATH
     *
     * can never be overwritten, must be set by pipelines itself for the
     * initial pipeline. will be taken over into each sub-pipeline.
     *
     * @param string $path absolute path to the project directory (deploy source path)
     *
     * @return void
     */
    public function setPipelinesProjectPath($path)
    {
        if (!LibFsPath::isAbsolute($path)) {
            throw new \InvalidArgumentException(sprintf('not an absolute path: "%s"', $path));
        }

        $this->setFirstPipelineVariable('PIPELINES_PROJECT_PATH', $path);
    }

    /**
     * @param string $option "-e" typically for Docker binary
     *
     * @return array of options (from $option) and values, ['-e', 'val1', '-e', 'val2', ...]
     */
    public function getArgs($option)
    {
        return Lib::merge(
            $this->collected,
            ArgsBuilder::optMap($option, $this->vars, true)
        );
    }

    /**
     * get a variables' value from the inherited
     * environment or null if not set
     *
     * @param string $name
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
     *
     * @return void
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
     *
     * @return void
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
     * get all variables collected so far
     *
     * @return array
     */
    public function getVariables()
    {
        return (array)$this->getResolver()->getVariables();
    }

    /**
     * @param array $inherit
     *
     * @return void
     */
    private function initInherit(array $inherit)
    {
        $this->inherit = array_filter($inherit, 'is_string');
    }

    /**
     * set an environment variable only if not yet set and in the first
     * pipeline.
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    private function setFirstPipelineVariable($name, $value)
    {
        if (
            isset($this->vars[$name])
            || !isset($this->vars['PIPELINES_ID'], $this->vars['PIPELINES_IDS'])
            || $this->vars['PIPELINES_IDS'] !== md5($this->vars['PIPELINES_ID'])
        ) {
            return;
        }

        $this->vars[$name] = $value;
    }
}
