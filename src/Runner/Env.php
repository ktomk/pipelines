<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

/**
 * Pipeline environment collaborator
 */
class Env
{
    private $vars;

    /**
     * @param array|null $inherit
     * @return Env
     */
    public static function create(array $inherit = null)
    {
        if (null === $inherit) {
            $inherit = $_SERVER;
        }

        $env = new Env();
        $env->initDefaultVars($inherit);

        return $env;
    }

    /**
     * Initialize default environment used in a Bitbucket Pipeline
     *
     * As the runner mimic some values, defaults are available
     *
     * @param array $inherit Environment variable store to inherit from
     */
    public function initDefaultVars(array $inherit)
    {
        $vars = array(
            'BITBUCKET_BOOKMARK' => null,
            'BITBUCKET_BRANCH' => null,
            'BITBUCKET_BUILD_NUMBER' => '0',
            'BITBUCKET_COMMIT' => '0000000000000000000000000000000000000000',
            'BITBUCKET_REPO_OWNER' => '' . $this->r($inherit['USER'], 'nobody'),
            'BITBUCKET_REPO_SLUG' => 'local-has-no-slug',
            'BITBUCKET_TAG' => null,
            'CI' => 'true',
        );

        foreach ($vars as $name => $value) {
            isset($inherit[$name]) ? $vars[$name] = $inherit[$name] : null;
        }

        $this->vars = $vars;
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
        );

        $var = $map[$type];

        if (!isset($this->vars[$var])) {
            $this->vars[$var] = $ref->getName();
        }
    }

    private function r(&$v, $d)
    {
        if (isset($v)) {
            return $v;
        }

        unset($v);
        return $d;
    }

    /**
     * @param string $option "-e" typically for Docker binary
     * @return array of options (from $option) and values, ['-e', 'val1', '-e', 'val2', ...]
     */
    public function getArgs($option)
    {
        $args = array();

        foreach ($this->getVarDefs() as $varDef) {
            $args[] = $option;
            $args[] = $varDef;
        }

        return $args;
    }

    /**
     * @return array w/ a string variable definition (name=value) per value
     */
    private function getVarDefs()
    {
        $array = array();

        foreach ((array)$this->vars as $name => $value) {
            if (isset($value)) {
                $array[] = sprintf('%s=%s', $name, $value);
            }
        }

        return $array;
    }
}
