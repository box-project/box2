<?php

namespace KevinGH\Box\Test;

use Herrera\PHPUnit\TestCase;
use KevinGH\Box\Helper\ConfigurationHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Makes it easier to test Box commands.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
abstract class CommandTestCase extends TestCase
{
    /**
     * The application.
     *
     * @var Application
     */
    protected $app;

    /**
     * The actual current working directory.
     *
     * @var string
     */
    protected $cwd;

    /**
     * The test current working directory.
     *
     * @var string
     */
    protected $dir;

    /**
     * The name of the command.
     *
     * @var string
     */
    private $name;

    /**
     * Returns the command to be tested.
     *
     * @return Command The command.
     */
    abstract protected function getCommand();

    /**
     * Returns the tester for the command.
     *
     * @return CommandTester The tester.
     */
    protected function getTester()
    {
        return new CommandTester($this->app->get($this->name));
    }

    /**
     * Restore the current working directory.
     */
    protected function tearDown()
    {
        chdir($this->cwd);

        parent::tearDown();
    }

    /**
     * Creates the application.
     */
    protected function setUp()
    {
        $this->cwd = getcwd();
        $this->dir = $this->createDir();

        chdir($this->dir);

        $this->app = new Application();
        $this->app->getHelperSet()->set(new ConfigurationHelper());

        $command = $this->getCommand();
        $this->name = $command->getName();

        $this->app->add($command);
    }
}