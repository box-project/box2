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

class AddTest extends CommandTestCase
{
    const COMMAND = 'add';

    public function testExecuteMainScript()
    {
        copy($this->getResource('example.phar'), 'example.phar');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => $this->getResource('example/box-default.json'),
            '--main' => true,
            '--replace' => true,
            'phar' => $file = realpath('example.phar'),
            'external' => $this->getResource('tests/main-add.php'),
            'internal' => 'bin/example'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            'Goodbye, world!',
            $this->command('php ' . escapeshellarg($file))
        );
    }

    public function testExecuteStub()
    {
        copy($this->getResource('example.phar'), 'example.phar');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => $this->getResource('example/box-default.json'),
            '--stub' => true,
            'phar' => $file = realpath('example.phar'),
            'external' => $this->getResource('tests/stub-add.php')
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            'Stub changed.',
            $this->command('php ' . escapeshellarg($file))
        );
    }

    public function testExecuteBin()
    {
        copy($this->getResource('example.phar'), 'example.phar');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => $this->getResource('example/box-default.json'),
            '--bin' => true,
            'phar' => $file = realpath('example.phar'),
            'external' => $this->getResource('tests/black.ico'),
            'internal' => 'res/icon.ico'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $phar = new Box($file);

        $phar->extractTo('.', 'res/icon.ico');

        unset($phar);

        $this->assertFileEquals(
            $this->getResource('tests/black.ico'),
            realpath('res/icon.ico')
        );
    }

    public function testExecuteMissingInternalPath()
    {
        copy($this->getResource('example.phar'), 'example.phar');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => $this->getResource('example/box-default.json'),
            '--main' => true,
            '--replace' => true,
            'phar' => $file = realpath('example.phar'),
            'external' => $this->getResource('tests/main-add.php')
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            'The internal path is required.',
            trim($this->tester->getDisplay())
        );
    }

    public function testExecuteCannotReplace()
    {
        copy($this->getResource('example.phar'), 'example.phar');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => $this->getResource('example/box-default.json'),
            '--main' => true,
            'phar' => $file = realpath('example.phar'),
            'external' => $this->getResource('tests/main-add.php'),
            'internal' => 'bin/example'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            'The path "bin/example" already exists in the PHAR',
            trim($this->tester->getDisplay())
        );
    }
}

