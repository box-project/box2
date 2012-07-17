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

    use InvalidArgumentException;

    /**
     * A JSON validation exception.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class JSONValidationException extends InvalidArgumentException
    {
        /**
         * The JSON errors.
         *
         * @type array
         */
        private $errors;

        /**
         * Sets the error message and errors.
         *
         * @param string $message The validation error message.
         * @param array $errors The errors.
         */
        public function __construct($message, array $errors = null)
        {
            parent::__construct($message);

            $this->errors = $errors;
        }

        /**
         * Returns the JSON errors.
         *
         * @return array The errors.
         */
        public function getErrors()
        {
            return $this->errors;
        }
    }