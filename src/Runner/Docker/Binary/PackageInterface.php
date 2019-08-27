<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Binary;

interface PackageInterface
{
    /**
     * Package array representation
     *
     * @return array
     */
    public function asPackageArray();
}
