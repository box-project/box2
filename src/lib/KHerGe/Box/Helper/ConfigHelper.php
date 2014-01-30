<?php

namespace KHerGe\Box\Helper;

use InvalidArgumentException;
use KHerGe\Box\Config\Definition;
use KHerGe\Box\Config\Loader\AbstractFileLoader;
use Phine\Path\Path;
use RuntimeException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Console\Helper\Helper;

/**
 * Helps load configuration settings from a variety of sources.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ConfigHelper extends Helper
{
    /**
     * The configuration definition.
     *
     * @var Definition
     */
    private $definition;

    /**
     * The configuration definition processor.
     *
     * @var Processor
     */
    private $processor;

    /**
     * Initializes the configuration helper.
     */
    public function __construct()
    {
        $this->definition = new Definition();
        $this->processor = new Processor();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'config';
    }

    /**
     * Loads configuration settings from a directory or file.
     *
     * @param string $path The configuration directory or file path.
     *
     * @return array The configuration settings.
     *
     * @throws InvalidArgumentException If $path does not exist.
     */
    public function load($path = null)
    {
        if (null !== $path) {
            if (is_dir($path)) {
                /** @var DelegatingLoader $loader */
                list($loader, $dir, $extensions) = $this->createLoader($path);
            } elseif (is_file($path)) {
                /** @var DelegatingLoader $loader */
                $loader = $this->createLoader(dirname($path));
                $dir = $loader[1];
                $loader = $loader[0];

                return $this->finalize(
                    $this->processor->processConfiguration(
                        $this->definition,
                        array($loader->load(basename($path)))
                    ),
                    $dir
                );
            } else {
                throw new InvalidArgumentException(
                    "The path \"$path\" does not exist."
                );
            }
        } else {
            list($loader, $dir, $extensions) = $this->createLoader();
        }

        $file = $this->findFile($dir, $extensions);

        return $this->finalize(
            $this->processor->processConfiguration(
                $this->definition,
                array($loader->load($file))
            ),
            $dir
        );
    }

    /**
     * Creates a new configuration loader.
     *
     * @param string $dir The configuration directory path.
     *
     * @return array The loader, configuration directory path, and supported
     *               file extensions.
     */
    private function createLoader($dir = null)
    {
        if ((null === $dir) || ('.' === $dir)) {
            $dir = Path::current();
        }

        $extensions = array();
        $locator = new FileLocator($dir);
        $loaders = array(
            '\KHerGe\Box\Config\Loader\JsonFileLoader' => null,
            '\KHerGe\Box\Config\Loader\PhpFileLoader' => null,
            '\KHerGe\Box\Config\Loader\YamlFileLoader' => null,
        );

        /** @var AbstractFileLoader $loader */
        foreach ($loaders as $class => &$loader) {
            $loader = new $class($locator);
            $extensions = array_merge($extensions, $loader->getSupportedExtensions());
        }

        return array(
            new DelegatingLoader(new LoaderResolver($loaders)),
            $dir,
            $extensions
        );
    }

    /**
     * Finalizes the configuration settings by making last minute changes.
     *
     * @param array  $config The processed configuration settings.
     * @param string $dir    The configuration directory path.
     *
     * @return array The finalized configuration settings.
     */
    private function finalize(array $config, $dir)
    {
        if (isset($config['sources']) && empty($config['sources']['base'])) {
            $config['sources']['base'] = $dir;
        }

        if (isset($config['git']) && empty($config['git']['path'])) {
            $config['git']['path'] = $config['sources']['base'];
        }

        return $config;
    }

    /**
     * Finds the configuration file to load.
     *
     * @param string $dir        The configuration directory path.
     * @param array  $extensions The supported file extensions.
     *
     * @return string The configuration file path.
     *
     * @throws RuntimeException If the configuration file could not be found,
     *                          or if too many configuration files were found.
     */
    private function findFile($dir, array $extensions)
    {
        $found = array(
            'dist' => array(),
            'normal' => array(),
        );

        foreach ($extensions as $extension) {
            $file = $dir . DIRECTORY_SEPARATOR . 'box.' . $extension;

            if (file_exists($file)) {
                $found['normal'][] = $file;
            } elseif (file_exists($file . '.dist')) {
                $found['dist'][] = $file . '.dist';
            }
        }

        if (empty($found['normal'])) {
            if (empty($found['dist'])) {
                throw new RuntimeException(
                    "The directory \"$dir\" did not contain the configuration file."
                );
            } elseif (count($found['dist']) > 1) {
                throw new RuntimeException(
                    "Many configuration files were found in \"$dir\". You will need to specify the one you want to use."
                );
            }

            return $found['dist'][0];
        } elseif (count($found['normal']) > 1) {
            throw new RuntimeException(
                "Many configuration files were found in \"$dir\". You will need to specify the one you want to use."
            );
        }

        return $found['normal'][0];
    }
}
