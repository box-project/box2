<?php

namespace KHerGe\Box\Config\Loader;

use Symfony\Component\Config\Loader\FileLoader;

/**
 * Provides support for both distribution (*.dist) and user configuration files.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
abstract class AbstractFileLoader extends FileLoader
{
    /**
     * Returns the supported file extensions.
     *
     * @return array The supported file extensions.
     */
    abstract public function getSupportedExtensions();

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        if (is_string($resource)) {
            $extension = pathinfo($resource, PATHINFO_EXTENSION);

            if ('dist' === $extension) {
                $extension = pathinfo(basename($resource, '.dist'), PATHINFO_EXTENSION);
            }

            return in_array($extension, $this->getSupportedExtensions());
        }

        return false;
    }
}
