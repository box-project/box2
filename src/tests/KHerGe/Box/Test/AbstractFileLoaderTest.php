<?php

namespace KHerGe\Box\Test;

use KHerGe\Box\Config\Loader\AbstractFileLoader;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Config\FileLocator;

/**
 * Sets up a file loader for testing.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
abstract class AbstractFileLoaderTest extends TestCase
{
    /**
     * The temporary configuration directory.
     *
     * @var string
     */
    protected $dir;

    /**
     * The file loader.
     *
     * @var AbstractFileLoader
     */
    protected $loader;

    /**
     * The test file locator.
     *
     * @var FileLocator
     */
    protected $locator;

    /**
     * The temporary file manager.
     *
     * @var Temp
     */
    protected $temp;

    /**
     * Returns the class name for the loader being tested.
     *
     * @return string The class name.
     */
    abstract protected function getLoaderClass();

    /**
     * @override
     */
    protected function setUp()
    {
        $class = $this->getLoaderClass();

        $this->temp = new Temp();
        $this->dir = $this->temp->createDir();
        $this->locator = new FileLocator($this->dir);
        $this->loader = new $class($this->locator);
    }
}
