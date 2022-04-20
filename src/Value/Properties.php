<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value;

use Countable;
use InvalidArgumentException;
use Ktomk\Pipelines\Lib;

/**
 * Properties value object (named set)
 *
 * @package Ktomk\Pipelines\Value
 */
class Properties implements Countable
{
    /**
     * @var array
     */
    private $properties = array();

    /**
     * import properties from an array
     *
     * @param array $array
     *
     * @return void
     */
    public function importPropertiesArray(array $array)
    {
        foreach ($array as $key => $value) {
            $this->properties[$key] = $value;
        }
    }

    /**
     * export a set of named entries as array
     *
     * @param array $keys named entries to export in two forms:
     *                    1. array of strings
     *                    2. array of two arrays of string, first is for
     *                       required, second for optional entries
     *
     * @throws InvalidArgumentException
     *
     * @return array export product
     */
    public function export(array $keys)
    {
        if (
            2 === count($keys)
            && array(0, 1) === array_keys($keys)
            && 2 === count(array_filter($keys, 'is_array'))
        ) {
            list($required, $optional) = $keys;
            $this->missingKeys($required);
            $keys = array_merge($required, $optional);
        }

        return $this->exportPropertiesByName($keys);
    }

    /**
     * export properties by name(s)
     *
     * properties not set are not exported
     *
     * @param array $keys
     * @param array $export [optional] array to extend
     *
     * @return array w/ exported properties
     */
    public function exportPropertiesByName(array $keys, array $export = array())
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->properties)) {
                $export[$key] = $this->properties[$key];
            }
        }

        return $export;
    }

    /**
     * has all named entities
     *
     * @param array|string $keys ...
     *
     * @return bool
     */
    public function has($keys)
    {
        $args = func_get_args();
        $allKeys = Lib::mergeArray($args);

        foreach ($allKeys as $key) {
            if (!array_key_exists($key, $this->properties)) {
                return false;
            }
        }

        return true;
    }

    /**
     * import named entries from array as properties
     *
     * @param array $array to import from
     * @param array $keys named entries to import
     *
     * @return array passed in array with the imported entries removed
     */
    public function import(array $array, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $this->properties[$key] = $array[$key];
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->properties;
    }

    /** Countable */

    #[\ReturnTypeWillChange]
    /**
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     */
    public function count()
    {
        return count($this->properties);
    }

    /**
     * obtain a list of keys that are not available in properties
     *
     * @param array $keys
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private function missingKeys(array $keys)
    {
        $missing = array_diff_key(array_flip($keys), $this->properties);
        if (!empty($missing)) {
            throw new InvalidArgumentException(sprintf(
                'property/ies "%s" required',
                implode('", "', array_keys($missing))
            ));
        }
    }
}
