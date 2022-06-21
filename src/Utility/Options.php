<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Utility\Option\Types;

/**
 * application options store
 */
class Options
{
    /**
     * @var array protected to make use of it in OptionsMock for tests
     */
    protected $definition;

    /**
     * @var Types used in the definition
     */
    protected $types;

    /**
     * @return Options
     */
    public static function create()
    {
        $definition = array(
            'docker.socket.path' => array('/var/run/docker.sock', null),
            'docker.client.path' => array('/usr/bin/docker', null),
            'script.runner' => array('/bin/sh', null),
            'script.bash-runner' => array(true, null),
            'script.exit-early' => array(false, null),
            'step.clone-path' => array('/app', Types::ABSPATH),
        );

        return new self($definition, new Types());
    }

    public function __construct(array $definition, Types $types = null)
    {
        $this->definition = $definition;
        $this->types = $types;
    }

    /**
     * @param string $name
     *
     * @return null|bool|string
     */
    final public function get($name)
    {
        if (isset($this->definition[$name][0])) {
            return $this->definition[$name][0];
        }

        return null;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return null|string
     */
    final public function verify($name, $value)
    {
        $type = isset($this->definition[$name][1]) ? $this->definition[$name][1] : null;

        return $this->types ? $this->types->verifyInput($value, $type) : $value;
    }
}
