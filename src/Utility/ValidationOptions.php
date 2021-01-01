<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use InvalidArgumentException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\Yaml\Yaml;

/**
 * Class ValidationOptions
 *
 * validate one or multiple bitbucket-pipelines.yml files against
 * the schema
 *
 * @package Ktomk\Pipelines\Utility
 */
class ValidationOptions implements Runnable
{
    /**
     * @var Args
     */
    private $args;

    /**
     * @var callable
     */
    private $output;

    /**
     * @var File
     */
    private $file;

    /**
     * bind options
     *
     * @param Args $args
     * @param Streams $streams
     * @param File $file
     *
     * @return ValidationOptions
     */
    public static function bind(Args $args, Streams $streams, File $file)
    {
        return new self($args, $streams, $file);
    }

    /**
     * FileOptions constructor.
     *
     * @param Args $args
     * @param callable $output
     * @param File $file
     */
    public function __construct(Args $args, $output, File $file)
    {
        $this->args = $args;
        $this->output = $output;
        $this->file = $file;
    }

    /**
     * run options
     *
     * @throws InvalidArgumentException
     * @throws StatusException
     * @throws \ReflectionException
     *
     * @return $this
     */
    public function run()
    {
        $didValidate = false;
        $allValid = true;

        while (null !== $verify = $this->args->getOptionOptionalArgument('validate', true)) {
            $didValidate = true;
            list($isValid) = $this->validate($verify);
            $isValid || $allValid = false;
        };

        if ($didValidate) {
            throw new StatusException(
                sprintf('validate done%s', $allValid ? '' : ', with errors'),
                $allValid ? 0 : 1
            );
        }

        return $this;
    }

    /**
     * @param string|true $verify
     *
     * @return array
     */
    private function validate($verify)
    {
        if (true === $verify) {
            $data = $this->file->getArray();
        } else {
            $data = Yaml::file($verify);
            if (null === $data) {
                throw new \UnexpectedValueException(sprintf('not a yaml file: %s', $verify));
            }
        }

        list($isValid, $validator) = $this->validateData($data);

        if (!$isValid) {
            foreach ($validator->getErrors(Validator::ERROR_DOCUMENT_VALIDATION) as $error) {
                call_user_func(
                    $this->output,
                    sprintf(
                        '%s[%s] %s',
                        true === $verify ? '' : sprintf('%s: ', $verify),
                        $error['property'],
                        $error['message']
                    )
                );
            }
        }

        return array($isValid, $validator);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function validateData(array $data)
    {
        // Validate
        $validator = new \JsonSchema\Validator();
        $path = __DIR__ . '/../../lib/pipelines/schema/pipelines-schema.json';
        if (0 !== strpos($path, 'phar:///', 0)) {
            $path = 'file://' . $path;
        }

        $validator->validate(
            $data,
            (object)array('$ref' => $path),
            Constraint::CHECK_MODE_TYPE_CAST | Constraint::CHECK_MODE_DISABLE_FORMAT
        );

        $isValid = $validator->isValid();

        return array($isValid, $validator);
    }
}
