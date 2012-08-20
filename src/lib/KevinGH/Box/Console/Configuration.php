<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console;

use ArrayObject;
use InvalidArgumentException;
use KevinGH\Box\Box;
use RuntimeException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Finder\Finder;

/**
 * The Box JSON schema file.
 *
 * @var string
 */
define('BOX_SCHEMA', realpath(__DIR__ . '/../../../../../res/schema.json'));

/**
 * Manages configuration settings for the Box console application.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Configuration extends ArrayObject
{
    /**
     * The configuration file path.
     *
     * @var string
     */
    private $file;

    /**
     * The helper set.
     *
     * @var HelperSet
     */
    private $helpers;

    /**
     * Sets the file path, default configuration settings, and processes
     * the values provided in the configuration settings.
     *
     * @param HelperSet $helpers  The helper set.
     * @param array     $defaults The default settings.
     * @param string    $file     The file path.
     */
    public function __construct(HelperSet $helpers, $file, $defaults = array())
    {
        $this->file = $file;
        $this->helpers = $helpers;

        parent::__construct(array_merge(
            array(
                'algorithm' => 'SHA1',
                'alias' => 'default.phar',
                'base-path' => null,
                'blacklist' => array(),
                'chmod' => null,
                'compression' => null,
                'directories' => array(),
                'directories-bin' => array(),
                'files' => array(),
                'files-bin' => array(),
                'finder' => array(),
                'finder-bin' => array(),
                'git-version' => null,
                'intercept' => false,
                'key' => null,
                'key-pass' => null,
                'main' => null,
                'metadata' => null,
                'output' => 'default.phar',
                'replacements' => array(),
                'stub' => null
            ),
            (array) $defaults
        ));

        $this->processConfig();
    }

    /**
     * Returns the files found using the configuration settings.
     *
     * @param boolean $bin Return list of binary files?
     *
     * @return array The list of relative and absolute paths.
     *
     * @throws InvalidArgumentException If a configuration setting is invalid.
     * @throws RuntimeException         If the list could not be generated.
     */
    public function getFiles($bin = false)
    {
        $files = array();
        $bin = $bin ? '-bin' : '';
        $cwd = $this->helpers->get('box')->getCurrentDir();

        if ($cwd !== $this['base-path']) {
            chdir($this['base-path']);
        }

        foreach ((array) $this["files$bin"] as $file) {
            if (false === is_file($file)) {
                throw new InvalidArgumentException(sprintf(
                    'The path "%s" is not a file or does not exist.',
                    $file
                ));
            }

            $absolute = realpath($file);

            $files[$this->getRelativeOf($absolute)] = $absolute;
        }

        foreach ((array) $this["directories$bin"] as $directory) {
            $finder = new Finder();

            $finder->files()->ignoreVCS(true);

            if (empty($bin)) {
                $finder->name('*.php');
            }

            $finder->in($directory);

            foreach ($finder as $file) {
                $absolute = $file->getRealPath();

                $files[$this->getRelativeOf($absolute)] = $absolute;
            }
        }

        foreach ((array) $this["finder$bin"] as $methods) {
            $finder = new Finder();

            $finder->files()->ignoreVCS(true);

            foreach ($methods as $method => $arguments) {
                if (false === method_exists($finder, $method)) {
                    throw new InvalidArgumentException(sprintf(
                        'The method "%s" was not found in the Finder class.',
                        $method
                    ));
                }

                foreach ((array) $arguments as $argument) {
                    $finder->$method($argument);
                }
            }

            foreach ($finder as $file) {
                $absolute = $file->getRealPath();

                $files[$this->getRelativeOf($absolute)] = $absolute;
            }
        }

        if ($cwd !== $this['base-path']) {
            chdir($cwd);
        }

        return $files;
    }

    /**
     * Returns the main script file path, if any.
     *
     * @return null|string The script file path.
     *
     * @throws InvalidArgumentException If the file does not exist.
     */
    public function getMainPath()
    {
        if ($this['main']) {
            $path = $this['base-path'] . DIRECTORY_SEPARATOR . $this['main'];

            if (false === is_file($path)) {
                throw new InvalidArgumentException(sprintf(
                    'The path "%s" is not a file or it does not exist.',
                    $this['main']
                ));
            }

            return $path;
        }
    }

    /**
     * Returns the configured output file path.
     *
     * @return string The output file path.
     */
    public function getOutputPath()
    {
        return $this['base-path'] . DIRECTORY_SEPARATOR . $this['output'];
    }

    /**
     * Returns the relative path based on the current base directory path.
     *
     * @param string $path The absolute path.
     *
     * @return string The relative path.
     */
    public function getRelativeOf($path)
    {
        return ltrim(str_replace($this['base-path'], '', $path), '\\/');
    }

    /**
     * Loads the configuration file and returns an instance.
     *
     * @param HelperSet $helpers The helper set.
     * @param string    $file    The configuration file.
     *
     * @return Configuration A Configuration instance.
     */
    public static function load(HelperSet $helpers, $file)
    {
        $json = $helpers->get('json');
        $data = $json->parseFile($file);
        $schema = $json->parseFile(BOX_SCHEMA);

        $json->validate($schema, $json);

        $config = new self($helpers, $file, $data);

        return $config;
    }

    /**
     * Processes the configuration settings.
     */
    public function processConfig()
    {
        if (empty($this['base-path'])) {
            $this['base-path'] = $this->helpers->get('box')->getCurrentDir();
        }

        if (is_string($this['algorithm'])) {
            if (false === defined('Phar::' . $this['algorithm'])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid signature algorithm constant: Phar::%s',
                    $this['algorithm']
                ));
            }

            $this['algorithm'] = constant('Phar::' . $this['algorithm']);
        }

        if (is_string($this['compression'])) {
            if (false === defined('Phar::' . $this['compression'])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid compression algorithm constant: Phar::%s',
                    $this['compression']
                ));
            }

            $this['compression'] = constant('Phar::' . $this['compression']);
        }

        $this['replacements'] = (array) $this['replacements'];

        if ($this['git-version']) {
            $git = $this->helpers->get('git');
            $repo = $this['base-path'] ?: $this->helpers->get('box')->getCurrentDir();

            $version = $git->getTag($repo) ?: $git->getCommit(true, $repo);

            $this['replacements'] = array_merge(
                array(
                    $this['git-version'] => $version
                ),
                $this['replacements']
            );
        }
    }
}

