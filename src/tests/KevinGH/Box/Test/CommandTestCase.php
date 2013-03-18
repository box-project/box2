<?php

namespace KevinGH\Box\Test;

use Herrera\PHPUnit\TestCase;
use KevinGH\Box\Helper\ConfigurationHelper;
use Symfony\Component\Console\Application;

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
    private $cwd;

    /**
     * The test current working directory.
     *
     * @var string
     */
    private $dir;

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
    }
}