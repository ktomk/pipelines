<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Definitions;

use Ktomk\Pipelines\File\Image;
use Ktomk\Pipelines\File\ParseException;

class Service
{
    /**
     * @var array
     */
    private $array;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Image
     */
    private $image;

    /**
     * @var array
     */
    private $variables = array();

    /**
     * Service constructor.
     *
     * @param string $name
     * @param array $array
     */
    public function __construct($name, array $array)
    {
        $this->name = (string)$name;
        $this->parse($array);
        $this->array = $array;
    }

    /**
     * @return Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array|string[]
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @param array $array
     *
     * @return void
     */
    private function parse(array $array)
    {
        if (!isset($array['image'])) {
            throw new ParseException("'image' required in service definition");
        }
        Image::validate($array);
        $this->image = new Image($array['image']);

        $this->parseVariables($array);
    }

    /**
     * @param array $array
     *
     * @return void
     */
    private function parseVariables(array $array)
    {
        if (!array_key_exists('variables', $array)) {
            return;
        }

        $variables = $array['variables'];
        if (!is_array($variables)) {
            throw new ParseException('variables must be a list of strings');
        }

        foreach ($variables as $name => $value) {
            if (null === $value) {
                throw new ParseException("variable ${name} should be a string value (it is currently null or empty)");
            }
            if (is_bool($value)) {
                throw new ParseException("variable ${name} should be a string (it is currently defined as a boolean)");
            }
        }

        $variables = array_map('strval', $variables);
        $this->variables = $variables;
    }
}
