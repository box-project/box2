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

use KevinGH\Box\Test\CommandTestCase;
use KevinGH\Box\Test\Dialog;
use Symfony\Component\Console\Output\OutputInterface;

class ExtractTest extends CommandTestCase
{
    const COMMAND = 'extract';

    public function testExecuteAll()
    {
        copy($this->getResource('example.phar'), 'example.phar');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            'phar' => $file = realpath('example.phar')
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertFileExists('example.phar-contents/bin/example');
        $this->assertFileExists('example.phar-contents/src/Put.php');
        $this->assertEquals(
            <<<OUTPUT
<prefix>EXTRACT</prefix> Extracting: $file
<prefix>EXTRACT</prefix> Done!

OUTPUT
            ,
            $this->tester->getDisplay()
        );
    }

    public function testExecuteWant()
    {
        copy($this->getResource('example.phar'), 'example.phar');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            'phar' => $file = realpath('example.phar'),
            '--want' => array('src/Put.php')
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertFileNotExists('example.phar-contents/bin/example');
        $this->assertFileExists('example.phar-contents/src/Put.php');
        $this->assertEquals(
            <<<OUTPUT
<prefix>EXTRACT</prefix> Extracting: $file
<prefix>EXTRACT</prefix> Done!

OUTPUT
            ,
            $this->tester->getDisplay()
        );
    }

    public function testExecuteNotExist()
    {
        $this->tester->execute(array(
            'command' => self::COMMAND,
            'phar' => 'example.phar'
        ));

        $this->assertEquals(
            'The path "example.phar" is not a file or does not exist.',
            trim($this->tester->getDisplay())
        );
    }
}

