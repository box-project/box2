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

class BuildTest extends CommandTestCase
{
    const COMMAND = 'build';

    public function testExecuteBoxStub()
    {
        $this->setupApp();

        $this->command->getHelperSet()->set(new Dialog('phpunit'));

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => 'box-generated.json'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            <<<OUTPUT
<prefix>BUILD</prefix> Building PHAR...
<prefix>STAGE</prefix> Removing any old PHARs...
<prefix>SETUP</prefix> Setting replacements...
<prefix>BUILD</prefix> s src/Put.php
<prefix>BUILD</prefix> b res/icon.ico
<prefix>BUILD</prefix> m bin/example
<prefix>SETUP</prefix> Enabling Phar::interceptFileFunc() in generated stub...
<prefix>SETUP</prefix> Using Box generated stub...
<prefix>SETUP</prefix> Setting metadata...
<prefix>SETUP</prefix> Using compression...
<prefix>SETUP</prefix> Setting mode to 0755
<prefix>BUILD</prefix> Done!

OUTPUT
            ,
            $this->tester->getDisplay()
        );

        $this->assertEquals(
            'Hello, world!',
            $this->command('php example.phar.gz')
        );
    }

    public function testExecuteCustomStub()
    {
        $this->setupApp();

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => 'box-custom.json'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            <<<OUTPUT
<prefix>BUILD</prefix> Building PHAR...
<prefix>STAGE</prefix> Removing any old PHARs...
<prefix>SETUP</prefix> Setting replacements...
<prefix>BUILD</prefix> s src/Put.php
<prefix>BUILD</prefix> m bin/example
<prefix>SETUP</prefix> Using custom stub...
<prefix>BUILD</prefix> Done!

OUTPUT
            ,
            $this->tester->getDisplay()
        );

        $this->assertEquals(
            'Hello, world!',
            $this->command('php example.phar')
        );
    }

    public function testExecuteDefaultStub()
    {
        $this->setupApp();

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => 'box-default.json'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            <<<OUTPUT
<prefix>BUILD</prefix> Building PHAR...
<prefix>STAGE</prefix> Removing any old PHARs...
<prefix>SETUP</prefix> Setting replacements...
<prefix>BUILD</prefix> s src/Put.php
<prefix>BUILD</prefix> m bin/example
<prefix>SETUP</prefix> Using Phar default stub...
<prefix>BUILD</prefix> Done!

OUTPUT
            ,
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/\Qfailed to open stream: phar error: "index.php"\E/',
            $this->command('php example.phar')
        );
    }

    public function testExecuteMainNotUsed()
    {
        $this->setupApp();

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => 'box-index.json'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            <<<OUTPUT
<prefix>BUILD</prefix> Building PHAR...
<prefix>STAGE</prefix> Removing any old PHARs...
<prefix>SETUP</prefix> Setting replacements...
<prefix>BUILD</prefix> s src/Put.php
<prefix>BUILD</prefix> s index.php
<prefix>BUILD</prefix> m bin/example
<prefix>SETUP</prefix> Main script will not be executable
<prefix>SETUP</prefix> Using Box generated stub...
<prefix>SETUP</prefix> Using compression...
<prefix>BUILD</prefix> Done!

OUTPUT
            ,
            $this->tester->getDisplay()
        );
    }

    public function testExecuteNoFilesAdded()
    {
        $this->setupApp();

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--configuration' => 'box-none.json'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            <<<OUTPUT
<prefix>BUILD</prefix> Building PHAR...
<prefix>STAGE</prefix> Removing any old PHARs...
<prefix>SETUP</prefix> Using Phar default stub...
<prefix>BUILD</prefix> No files were added.

OUTPUT
            ,
            $this->tester->getDisplay()
        );
    }
}

