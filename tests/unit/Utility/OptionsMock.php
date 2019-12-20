<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use phpDocumentor\Reflection\Types\AbstractList;
use phpDocumentor\Reflection\Types\Parent_;

/**
 * application options store (for unit tests)
 */
class OptionsMock extends Options
{
    /**
     * @return OptionsMock
     */
    public static function create()
    {
        return new self(parent::create()->definition);
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function define($name, $value)
    {
        $this->definition[$name] = array($value);

        return $this;
    }
}
