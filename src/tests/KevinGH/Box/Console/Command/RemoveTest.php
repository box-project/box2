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
use Symfony\Component\Console\Output\OutputInterface;

class RemoveTest extends CommandTestCase
{
    const COMMAND = 'remove';

    public function testExecute()
    {
        copy($this->getResource('example.phar'), 'example.phar');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            'phar' => realpath('example.phar'),
            'internal' => array('bin/example', 'src/Put.php')
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            '<prefix>REMOVE</prefix> Successfully removed the file(s)!',
            trim($this->tester->getDisplay())
        );
    }
}

