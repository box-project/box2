<?php

namespace KHerGe\Box\Tests\Unit\Config\Loader;

use KHerGe\Box\Test\AbstractFileLoaderTest as TestCase;

/**
 * Performs unit testing on `PhpFileLoader`.
 *
 * @see KHerGe\Box\Config\Loader\PhpFileLoader
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class PhpFileLoaderTest extends TestCase
{
    /**
     * Make sure that the expected file extensions are returned.
     */
    public function testGetSupportedExtensions()
    {
        $this->assertEquals(
            array('php'),
            $this->loader->getSupportedExtensions(),
            'The supported file extensions should be returned.'
        );
    }

    /**
     * Make sure that we can load the configuration file.
     */
    public function testLoad()
    {
        $data = array('rand' => rand());

        file_put_contents(
            "{$this->dir}/test.php",
            '<?php return ' . var_export($data, true) . ';'
        );

        $this->assertEquals(
            $data,
            $this->loader->load('test.php'),
            'The configuration data should be loaded.'
        );
    }

    /**
     * @override
     */
    protected function getLoaderClass()
    {
        return 'KHerGe\Box\Config\Loader\PhpFileLoader';
    }
}
