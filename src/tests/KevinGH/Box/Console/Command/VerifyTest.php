<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Command;

use Exception;
use KevinGH\Box\Box;
use KevinGH\Box\Test\CommandTestCase;
use Symfony\Component\Console\Output\OutputInterface;

class VerifyTest extends CommandTestCase
{
    const COMMAND = 'verify';

    public function testExecuteValid()
    {
        $this->tester->execute(array(
            'command' => self::COMMAND,
            'phar' => $this->getResource('example.phar')
        ));

        $this->assertEquals('The PHAR was verified using SHA-1.', trim($this->tester->getDisplay()));
    }

    public function testExecuteInvalid()
    {
        file_put_contents('test.phar', str_replace(
            '__HALT_COMPILER',
            '',
            $this->getResource('example.phar', true)
        ));

        try {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => realpath('test.phar')
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        } catch (Exception $exception) {
        }

        $this->assertTrue(isset($exception));
        $this->assertEquals('The PHAR failed verification.', trim($this->tester->getDisplay()));
    }
}

