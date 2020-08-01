<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

interface StatusRunnable extends Runnable
{
    /**
     * @return int
     */
    public function run();
}
