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
    public static function create(array $inherit = array())
    {
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
        $inheritable = array(
            'BITBUCKET_BOOKMARK' => null,
            'BITBUCKET_BRANCH' => null,
            'BITBUCKET_BUILD_NUMBER' => '0',
            'BITBUCKET_COMMIT' => '0000000000000000000000000000000000000000',
            'BITBUCKET_REPO_OWNER' => '' . $this->r($inherit['USER'], 'nobody'),
            'BITBUCKET_REPO_SLUG' => 'local-has-no-slug',
            'BITBUCKET_TAG' => null,
            'CI' => 'true',
            'PIPELINES_CONTAINER_NAME' => null,
            'PIPELINES_IDS' => null,
            'PIPELINES_PARENT_CONTAINER_NAME' => null,
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
        );

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

    private function r(&$v, $d)
    {
        if (isset($v)) {
            return $v;
        }

        unset($v);
        return $d;
    }

    /**
     * set the pipelines environment's running pipeline id
     *
     * @param string $id of pipeline, e.g. "default" or "branch/feature/*"
     * @return bool whether was used before (endless pipelines in pipelines loop)
     */
    public function setPipelinesId($id)
    {
        $list = $this->getValue('PIPELINES_IDS');
        $hashes = preg_split('~\s+~', $list, -1, PREG_SPLIT_NO_EMPTY);
        $hashes = array_map('strtolower', $hashes);

        $idHash = md5($id);
        $hasId = in_array($idHash, $hashes, true);
        $hashes[] = $idHash;

        $this->vars['PIPELINES_ID'] = $id;
        $this->vars['PIPELINES_IDS'] = implode(' ', $hashes);

        return $hasId;
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
     * get a variables value or null if not set
     *
     * @param string $name
     * @return string|null
     */
    public function getValue($name)
    {
        return isset($this->vars[$name])
            ? $this->vars[$name]
            : null;
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
