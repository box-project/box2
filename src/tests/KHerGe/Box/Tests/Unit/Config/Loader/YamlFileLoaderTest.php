<?php

namespace KHerGe\Box\Tests\Unit\Config\Loader;

use KHerGe\Box\Test\AbstractFileLoaderTest as TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Performs unit testing on `YamlFileLoader`.
 *
 * @see KHerGe\Box\Config\Loader\PhpFileLoader
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class YamlFileLoaderTest extends TestCase
{
    /**
     * Make sure that the expected file extensions are returned.
     */
    public function testGetSupportedExtensions()
    {
        $this->assertEquals(
            array('yaml', 'yml'),
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

        file_put_contents("{$this->dir}/test.yml", Yaml::dump($data));

        $this->assertEquals(
            $data,
            $this->loader->load('test.yml'),
            'The configuration data should be loaded.'
        );
    }

    /**
     * @override
     */
    protected function getLoaderClass()
    {
        return 'KHerGe\Box\Config\Loader\YamlFileLoader';
    }
}
