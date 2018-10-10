<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\File\Image;

/**
 * DockerLogin - docker login authentication provider
 *
 * @package Ktomk\Pipelines\Runner
 */
class DockerLogin
{
    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var callable
     */
    private $resolver;

    /**
     * @var string path to docker config file (~/.docker/config.json)
     */
    private $path;

    /**
     * DockerLogin constructor.
     *
     * @param Exec $exec
     * @param callable $resolver
     * @param string $path [optional] path to docker config file (auth storage)
     */
    public function __construct(Exec $exec, $resolver, $path = null)
    {
        $this->exec = $exec;
        $this->resolver = $resolver;
        $this->path = null !== $path
            ? $path
            : $this->getDockerConfigPathFromEnvironment();
    }

    /**
     * Establish login for image
     *
     * @param Image $image
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function byImage(Image $image)
    {
        $properties = $image->getProperties();

        # image needs to have properties if login is applicable
        if (!count($properties)) {
            return;
        }

        # private docker hub login
        $required = array('username', 'password');
        // FIXME(tk): check hostname component from image name
        if ($properties->has($required) && !$this->dockerLoginHasAuth()) {
            $props = array($required, array('email'));
            $resolver = $this->resolver;
            $login = $resolver($properties->export($props));
            $exec = $this->exec;
            $exec->capture('docker', array(
                'login', '-u', $login['username'], '-p', $login['password'],
            ));
        }
    }

    /**
     * helper function to tell if docker hub login is already available or not
     *
     * useful to prevent overwriting local auth
     *
     * @param string $authUri URI to check authorization again
     * @return bool
     */
    public function dockerLoginHasAuth($authUri = 'https://index.docker.io/v1/')
    {
        $buffer = @file_get_contents($this->path);
        $array = json_decode((string)$buffer, true);

        return isset($array['auths'][$authUri]['auth']);
    }

    /**
     * obtain docker config path (~/.docker/config.json) from environment
     *
     * @return string
     */
    public function getDockerConfigPathFromEnvironment()
    {
        $home = getenv('HOME');

        return ($home ?: '~') . '/.docker/config.json';
    }
}
