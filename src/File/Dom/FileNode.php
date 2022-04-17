<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Dom;

use Ktomk\Pipelines\File\File;

interface FileNode
{
    /**
     * Get file which is the owner document
     *
     * @return File
     */
    public function getFile();
}
