<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\ArgsException;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\Runner\Env;
use Ktomk\Pipelines\Runner\Reference;

/**
 * Class EnvParser
 *
 * @package Ktomk\Pipelines\Utility
 */
class EnvParser
{
    /**
     * @var Args
     */
    private $arguments;

    public static function create(Args $arguments)
    {
        return new self($arguments);
    }

    /**
     * EnvParser constructor.
     *
     * @param Args $arguments
     */
    public function __construct(Args $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param array $inherit from this environment
     * @param Reference $reference
     * @param string $workingDir
     *
     * @throws InvalidArgumentException
     * @throws ArgsException
     *
     * @return Env
     */
    public function parse(array $inherit, $reference, $workingDir)
    {
        $args = $this->arguments;

        Lib::v($inherit['BITBUCKET_REPO_SLUG'], basename($workingDir));

        $env = Env::create($inherit);

        $noDotEnvFiles = $args->hasOption('no-dot-env-files');
        $noDotEnvDotDist = $args->hasOption('no-dot-env-dot-dist');

        if (false === $noDotEnvFiles) {
            $filesToCollect = array();
            if (false === $noDotEnvDotDist) {
                $filesToCollect[] = $workingDir . '/.env.dist';
            }
            $filesToCollect[] = $workingDir . '/.env';
            $env->collectFiles($filesToCollect);
        }

        $env->collect($args, array('e', 'env', 'env-file'));
        $resolved = $env->getVariables();

        $env->initDefaultVars($resolved + $inherit);
        $env->addReference($reference);

        return $env;
    }
}
