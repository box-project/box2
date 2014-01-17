<?php

namespace KHerGe\Box\Config\Loader;

/**
 * Supports the loading of PHP configuration files.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class PhpFileLoader extends AbstractFileLoader
{
    /**
     * @override
     */
    public function getSupportedExtensions()
    {
        return array('php');
    }

    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null)
    {
        /** @noinspection PhpIncludeInspection */
        return require_once $this->locator->locate($resource);
    }
}
