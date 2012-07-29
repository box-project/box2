<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Helper;

use InvalidArgumentException;
use JsonSchema\Validator;
use KevinGH\Box\Console\Exception\JSONValidationException;
use RuntimeException;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Helper\Helper;

/**
 * The configuration schema file path.
 *
 * @type string
 */
define('BOX_SCHEMA_FILE', __DIR__ . '/../../../../../../res/schema.json');

/**
 * Parses and validates JSON files.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class JSON extends Helper
{
    /** {@inheritDoc} */
    public function getName()
    {
        return 'json';
    }

    /**
     * Parses the JSON string, checking for errors.
     *
     * @param string  $file   The file path.
     * @param string  $string The JSON data.
     * @param boolean $array  Return as associative array instead of object?
     *
     * @return mixed The result.
     */
    public function parse($file, $string, $array = true)
    {
        if (null === ($data = json_decode($string, $array))) {
            if (JSON_ERROR_NONE !== json_last_error()) {
                $this->validateSyntax($file, $string);

                // @codeCoverageIgnoreStart
            }
            // @codeCoverageIgnoreEnd
        }

        return $data;
    }

    /**
     * Parses the JSON file, checking for errors.
     *
     * @param string  $file  The file path.
     * @param boolean $array Return as associative array instead of object?
     *
     * @return mixed The result.
     *
     * @throws InvalidArgumentException If the file does not exist.
     * @throws RuntimeException         If the file could not be read.
     */
    public function parseFile($file, $array = true)
    {
        if (false === file_exists($file)) {
            throw new InvalidArgumentException(sprintf(
                'The file "%s" does not exist.',
                $file
            ));
        }

        if (false === ($string = @ file_get_contents($file))) {
            $error = error_get_last();

            throw new RuntimeException(sprintf(
                'The file "%s" could not be read: %s',
                $file,
                $error['message']
            ));
        }

        return $this->parse($file, $string, $array);
    }

    /**
     * Validates the data against the schema.
     *
     * @param string $file The file path.
     * @param mixed  $data The parsed JSON data.
     *
     * @throws JSONException If the data is invalid.
     */
    public function validate($file, $data)
    {
        static $schema = null;

        if (null === $schema) {
            $schema = $this->parseFile(BOX_SCHEMA_FILE, false);
        }

        $validator = new Validator;

        $validator->check(is_array($data) ? (object) $data : $data, $schema);

        if (false === $validator->isValid()) {
            $errors = array();

            foreach ($validator->getErrors() as $error) {
                $errors[] = (empty($error['property']) ? '' : $error['property'] . ': ')
                          . $error['message'];
            }

            throw new JSONValidationException(
                sprintf('The file "%s" is not valid.', $file),
                $errors
            );
        }
    }

    /**
     * Validates the syntax of the file.
     *
     * @param string $file The file path.
     * @param string $data The JSON data.
     *
     * @throws JSONValidationException If the file is invalid.
     * @throws ParsingException        If the file is invalid.
     */
    public function validateSyntax($file, $data)
    {
        $parser = new JsonParser;

        if (null === ($result = $parser->lint($data))) {
            if (JSON_ERROR_UTF8 === json_last_error()) {
                throw new JSONValidationException(sprintf(
                    'The file "%s" is not valid UTF-8.',
                    $file
                ));
            }
        } else {
            throw new ParsingException(sprintf(
                "The file \"%s\" does not contain valid JSON data:\n%s",
                $file,
                $result->getMessage()
            ), $result->getDetails());
        }
    }
}