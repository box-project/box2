<?php

namespace KHerGe\Box\Tests\Unit\Config\Loader;

use KHerGe\Box\Config\Loader\AbstractFileLoader;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Performs unit testing on `AbstractFileLoader`.
 *
 * @see KHerGe\Box\Config\Loader\AbstractFileLoader
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AbstractFileLoaderTest extends TestCase
{
    /**
     * The mock object for the abstract file loader.
     *
     * @var AbstractFileLoader|MockObject
     */
    private $loader;

    /**
     * Make sure that the loader can handle .dist files.
     */
    public function testSupports()
    {
        $this
            ->loader
            ->expects($this->exactly(3))
            ->method('getSupportedExtensions')
            ->will($this->returnValue(array('php')));

        $this->assertTrue(
            $this->loader->supports('test.php'),
            'The non-.dist file should be supported.'
        );

        $this->assertTrue(
            $this->loader->supports('test.php.dist'),
            'The .dist file should be supported.'
        );

        $this->assertFalse(
            $this->loader->supports('test.xml.dist'),
            'The file should not be supported.'
        );

        $this->assertFalse(
            $this->loader->supports(array()),
            'The array resource should not be supported.'
        );
    }

    /**
     * Creates a mock object for the abstract file loader.
     */
    protected function setUp()
    {
        $this->loader = $this->getMockForAbstractClass(
            'KHerGe\Box\Config\Loader\AbstractFileLoader',
            array(),
            '',
            false
        );
    }
}
