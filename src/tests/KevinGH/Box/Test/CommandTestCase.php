<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Test;

use KevinGH\Box\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * A test case for Box commands.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class CommandTestCase extends TestCase
{
    /**
     * The command being tested.
     *
     * @var string
     */
    const COMMAND = 'COMMAND_NAME';

    /**
     * The command line application.
     *
     * @var Application
     */
    protected $app;

    /**
     * The command.
     *
     * @var Command
     */
    protected $command;

    /**
     * The command tester.
     *
     * @var CommandTester
     */
    protected $tester;

    /**
     * Sets up the command.
     */
    protected function setUp($name = 'Box', $version = '@package_version@')
    {
        parent::setUp();

        $this->app = new Application($name, $version);
        $this->command = $this->app->find(static::COMMAND);
        $this->tester = new CommandTester($this->command);
    }

    /**
     * Sets up the example application in the temporary directory.
     */
    protected function setUpApp()
    {
        $this->copy(RESOURCE_PATH . '/example', $this->currentDir);
    }
}

