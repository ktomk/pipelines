<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Dom;

use Ktomk\Pipelines\File\File;

interface FileNode
{
    /**
     * Get file which is the owner document
     *
     * returns NULL for unassociated nodes
     *
     * if a getFile() implementation throws while the node is not
     * associated it is undefined behaviour
     *
     * @return null|File
     */
    public function getFile();
}
