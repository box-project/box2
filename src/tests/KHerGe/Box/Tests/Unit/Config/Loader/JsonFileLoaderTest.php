<?php

namespace KHerGe\Box\Tests\Unit\Config\Loader;

use KHerGe\Box\Test\AbstractFileLoaderTest as TestCase;

/**
 * Performs unit testing on `JsonFileLoader`.
 *
 * @see KHerGe\Box\Config\Loader\JsonFileLoader
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class JsonFileLoaderTest extends TestCase
{
    /**
     * Make sure that the expected file extensions are returned.
     */
    public function testGetSupportedExtensions()
    {
        $this->assertEquals(
            array('json'),
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

        file_put_contents("{$this->dir}/test.json", json_encode($data));

        $this->assertEquals(
            $data,
            $this->loader->load('test.json'),
            'The configuration data should be loaded.'
        );
    }

    /**
     * Make sure that an exception is thrown for invalid JSON data.
     */
    public function testLoadInvalidData()
    {
        file_put_contents("{$this->dir}/test.json", '{');

        $this->setExpectedException(
            'RuntimeException',
            'Syntax error.'
        );

        $this->loader->load('test.json');
    }

    /**
     * @override
     */
    protected function getLoaderClass()
    {
        return 'KHerGe\Box\Config\Loader\JsonFileLoader';
    }
}
