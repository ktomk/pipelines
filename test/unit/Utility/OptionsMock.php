<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Utility\Option\Types;

/**
 * application options store (for unit tests)
 */
class OptionsMock extends Options
{
    /**
     * @param null|Types $types [optional]
     *
     * @return OptionsMock
     */
    public static function create(Types $types = null)
    {
        return new self(parent::create()->definition, $types);
    }

    /**
     * @param string $name of option
     * @param bool|int|string $value default value
     * @param null|int $type [optional]
     *
     * @return $this
     */
    public function define($name, $value, $type = null)
    {
        $this->definition[$name] = array($value, $type);

        return $this;
    }
}
