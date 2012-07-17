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

    use PHPUnit_Framework_TestCase;

    class JSONValidationExceptionTest extends PHPUnit_Framework_TestCase
    {
        public function testException()
        {
            $errors = array('rand' => rand());

            $exception = new JSONValidationException('Test message.', $errors);

            $this->assertEquals($errors, $exception->getErrors());
        }
    }