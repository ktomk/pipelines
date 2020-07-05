<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

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
     * @return Options
     */
    public static function create()
    {
        $definition = array(
            'docker.socket.path' => array('/var/run/docker.sock'),
            'docker.client.path' => array('/usr/bin/docker'),
            'script.exit-early' => array(false),
            'step.clone-path' => array('/app'),
        );

        return new self($definition);
    }

    public function __construct(array $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @param string $name
     *
     * @return null|string
     */
    final public function get($name)
    {
        if (isset($this->definition[$name][0])) {
            return $this->definition[$name][0];
        }

        return null;
    }
}
