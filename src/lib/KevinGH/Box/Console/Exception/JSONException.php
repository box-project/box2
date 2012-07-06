<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console\Exception;

    use RuntimeException;

    /**
     * A JSON exception.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class JSONException extends RuntimeException
    {
        /**
         * The JSON error messages.
         *
         * @type array
         */
        private static $errors = array(
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
            JSON_ERROR_NONE => 'No error has occurred',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_SYNTAX => 'Syntax error',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );

        /**
         * Sets the JSON error message.
         *
         * @param integer $code The JSON error code.
         */
        public function __construct($code)
        {
            if (isset(self::$errors[$code]))
            {
                parent::__construct(self::$errors[$code], $code);
            }

            else
            {
                parent::__construct('Unknown error code', $code);
            }
        }
    }