<?php

namespace KHerGe\Box\Config\Loader;

use Symfony\Component\Yaml\Yaml;

/**
 * Supports the loading of YAML configuration files.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class YamlFileLoader extends AbstractFileLoader
{
    /**
     * @override
     */
    public function getSupportedExtensions()
    {
        return array('yaml', 'yml');
    }

    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null)
    {
        return Yaml::parse($this->locator->locate($resource));
    }
}
