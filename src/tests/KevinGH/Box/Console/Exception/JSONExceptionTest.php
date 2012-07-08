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

    use KevinGH\Box\Test\TestCase;

    class JSONExceptionTest extends TestCase
    {
        /**
         * @expectedException KevinGH\Box\Console\Exception\JSONException
         * @expectedExceptionMessage Syntax error
         */
        public function testValidCode()
        {
            throw new JSONException(JSON_ERROR_SYNTAX);
        }

        /**
         * @expectedException KevinGH\Box\Console\Exception\JSONException
         * @expectedExceptionMessage Unknown error code
         */
        public function testInvalidCode()
        {
            throw new JSONException(1234567890);
        }
    }