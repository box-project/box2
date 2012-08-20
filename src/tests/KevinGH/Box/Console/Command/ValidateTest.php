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

use KevinGH\Box\Box;
use KevinGH\Box\Test\CommandTestCase;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateTest extends CommandTestCase
{
    const COMMAND = 'validate';

    public function testExecuteValid()
    {
        $this->tester->execute(array(
            'command' => self::COMMAND,
            'configuration' => $this->getResource('example/box-generated.json')
        ));

        $this->assertEquals('The configuration file is valid.', trim($this->tester->getDisplay()));
    }

    public function testExecuteInvalid()
    {
        try {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                'configuration' => $this->getResource('example/box-invalid.json')
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        } catch (ParsingException $exception) {
        }

        $this->assertTrue(isset($exception));
        $this->assertEquals('The configuration file is invalid.', trim($this->tester->getDisplay()));
    }
}

