<?php

namespace KHerGe\Box\Config\Loader;

use RuntimeException;

/**
 * Supports the loading of JSON configuration files.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class JsonFileLoader extends AbstractFileLoader
{
    /**
     * @override
     */
    public function getSupportedExtensions()
    {
        return array('json');
    }

    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null)
    {
        $file = $this->locator->locate($resource);

        // @codeCoverageIgnoreStart
        if (false === ($data = file_get_contents($file))) {
            throw new RuntimeException(
                "The file \"$file\" could not be read."
            );
        }
        // @codeCoverageIgnoreEnd

        $data = json_decode($data, true);

        if (JSON_ERROR_NONE !== ($code = json_last_error())) {
            // @codeCoverageIgnoreStart
            switch ($code) {
                case JSON_ERROR_DEPTH:
                    $code = 'The maximum stack depth has been exceeded.';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $code = 'Invalid or malformed JSON.';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $code = 'Control character error, possibly incorrectly encoded.';
                    break;
                case JSON_ERROR_SYNTAX:
                    $code = 'Syntax error.';
                    break;
                case JSON_ERROR_UTF8:
                    $code = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                    break;
                default:
                    $code = "(error code: $code)";
            }
            // @codeCoverageIgnoreEnd

            throw new RuntimeException($code);
        }

        return $data;
    }
}
