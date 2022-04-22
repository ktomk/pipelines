<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\File;

use JsonSchema\Constraints\Constraint;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Yaml\Yaml;

/**
 * @coversNothing
 */
class ValidationTest extends TestCase
{
    /**
     * validate own bitbucket-pipelines.yml file against schema
     */
    public function testValidateProjectFile()
    {
        list($data, $validator) = $this->validatorByFixture(
            __DIR__ . '/../../../bitbucket-pipelines.yml',
            __DIR__ . '/../../../lib/pipelines/schema/pipelines-schema.json',
            Constraint::CHECK_MODE_DISABLE_FORMAT
        );
        $this->debugValidator($validator);
        self::assertTrue($validator->isValid());
    }

    /**
     * validate a file with knowing validation error
     */
    public function testInvalidPipelinesFile()
    {
        list($data, $validator) = $this->validatorByFixture(
            __DIR__ . '/../../data/yml/invalid/pipeline-step.yml',
            __DIR__ . '/../../../lib/pipelines/schema/pipelines-schema.json',
            Constraint::CHECK_MODE_DISABLE_FORMAT
        );
        $this->debugValidator($validator);
        self::assertFalse($validator->isValid());
    }

    /**
     * justinrainbow / json-schema flaw on "const" usage (regression test)
     */
    public function testValidationConstInOneOf()
    {
        $prefix = __DIR__ . '/_validation/';

        $yamlFile = $prefix . 'test-01.yml';

        // test for regression on "const" constraint in "oneOf"
        list($data, $validator) = $this->validatorByFixture($yamlFile, $prefix . 'test-01-schema-fail.json');
        self::assertFalse($validator->isValid());

        // demonstrate "enum" work-around working
        list($data, $validator) = $this->validatorByFixture($yamlFile, $prefix . 'test-01-schema-ok.json');
        self::assertTrue($validator->isValid());

        $this->debugValidator($validator);
    }

    /**
     * test disable format requirement (fuller yaml)
     *
     * for a useful validation to work, formats (e.g. for "email") must be disabled
     * for schema validation as pipelines supports variable substitution for
     * such properties
     */
    public function testValidationRequiresFormatsDisabled()
    {
        $prefix = __DIR__ . '/_validation/';
        $yamlFile = $prefix . 'test-02.yml';

        // test for image parse error on pipeline step
        list($data, $validator) = $this->validatorByFixture($yamlFile, $prefix . 'test-02-schema-fail.json');
        self::assertFalse($validator->isValid());

        // test for image parse error on pipeline step
        list($data, $validator) = $this->validatorByFixture(
            $yamlFile,
            $prefix . 'test-02-schema-fail.json',
            Constraint::CHECK_MODE_DISABLE_FORMAT
        );
        $this->debugValidator($validator);
        self::assertTrue($validator->isValid());
    }

    /**
     * test disable format requirement (reduced yaml)
     *
     * for a useful validation to work, formats (e.g. for "email") must be disabled
     * for schema validation as pipelines supports variable substitution for
     * such properties
     */
    public function testValidationRequiresFormatsDisabledReducedFixtures()
    {
        $prefix = __DIR__ . '/_validation/';
        $yamlFile = $prefix . 'test-03.yml';

        // test for image parse error on pipeline step
        list($data, $validator) = $this->validatorByFixture($yamlFile, $prefix . 'test-03-schema-fail.json');
        self::assertFalse($validator->isValid());

        // test for image w/o parse error disabling format
        list($data, $validator) = $this->validatorByFixture(
            $yamlFile,
            $prefix . 'test-03-schema-fail.json',
            Constraint::CHECK_MODE_DISABLE_FORMAT
        );
        $this->debugValidator($validator);
        self::assertTrue($validator->isValid());
    }

    private function validatorByFixture($yamlFile, $schemaFile, $checkMode = null)
    {
        $data = Yaml::file($yamlFile);

        null === $checkMode && $checkMode = 0;
        $checkMode |= Constraint::CHECK_MODE_TYPE_CAST;

        // Validate
        $validator = new \JsonSchema\Validator();
        $validator->validate(
            $data,
            (object)array('$ref' => 'file://' . $schemaFile),
            $checkMode
        );

        return array($data, $validator);
    }

    private function debugValidator($validator)
    {
        // do not output unless debugOutput is set
        if (empty($this->debugOutput)) {
            return;
        }

        if ($validator->isValid()) {
            echo "The supplied JSON validates against the schema.\n";
        } else {
            echo "JSON does not validate. Violations:\n";
            foreach ($validator->getErrors() as $error) {
                printf("[%s] %s\n", $error['property'], $error['message']);
            }
        }
    }
}
