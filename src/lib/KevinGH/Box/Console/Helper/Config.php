<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Helper;

use ArrayObject;
use InvalidArgumentException;
use KevinGH\Box\Console\Exception\JSONException;
use Phar;
use RuntimeException;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Manages the loading and use of configuration settings.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Config extends ArrayObject implements HelperInterface
{
    /**
     * The helper set.
     *
     * @type HelperSet
     */
    private $helperSet;

    /**
     * Sets the default configuration settings.
     *
     * @param array $input The configuration settings.
     */
    public function __construct(array $input = array())
    {
        parent::__construct(array_merge(
            array(
                'algorithm' => Phar::SHA1,
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
            $input
        ));
    }

    /**
     * Finds the appropriate configuration file.
     *
     * @param string $file The optional file path.
     *
     * @return string The found configuration file path.
     *
     * @throws InvalidArgumentException If the file does not exist.
     */
    public function find($file)
    {
        if ($file) {
            if (false === file_exists($file)) {
                throw new InvalidArgumentException('The configuration file does not exist.');
            }

            $file = realpath($file);
        } elseif (file_exists('box.json')) {
            $file = realpath('box.json');
        } elseif (file_exists('box.dist.json')) {
            $file = realpath('box.dist.json');
        } else {
            throw new RuntimeException('No configuration file found.');
        }

        return $file;
    }

    /**
     * Returns the current work directory path.
     *
     * @return string The directory path.
     */
    public function getCurrentDir()
    {
        if (false === ($cwd = getcwd())) {
            if (isset($_SERVER['PWD'])) {
                $cwd = $_SERVER['PWD'];
            } else {
                throw new RuntimeException('Could not get current working directory path.');
            }
        }

        return $cwd;
    }

    /**
     * Returns a list of files found using the configuration.
     *
     * @param boolean $bin Use binary-safe paths?
     *
     * @return array The list of files.
     *
     * @throws InvalidArgumentException If a path is not a file.
     */
    public function getFiles($bin = false)
    {
        $bin = $bin ? '-bin' : '';

        $files = array();

        if (is_dir($this['base-path'])) {
            $pwd = $this->getCurrentDir();

            chdir($this['base-path']);
        }

        foreach ((array) $this["files$bin"] as $file) {
            if (false === is_file($file)) {
                throw new InvalidArgumentException(sprintf(
                    'The path is not a file: %s',
                    $file
                ));
            }

            $absolute = realpath($file);

            $files[$this->relativeOf($absolute)] = $absolute;
        }

        foreach ((array) $this["directories$bin"] as $dir) {
            $finder = new Finder;

            $finder->files()
                   ->ignoreVCS(true)
                   ->name('*.php')
                   ->in($dir);

            foreach ($finder as $file) {
                $absolute = $file->getRealPath();

                $files[$this->relativeOf($absolute)] = $absolute;
            }
        }

        foreach ((array) $this["finder$bin"] as $methods) {
            $finder = new Finder;

            $finder->files()->ignoreVCS(true);

            foreach ($methods as $method => $arguments) {
                if (false === method_exists($finder, $method)) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid Finder setting: %s',
                        $method
                    ));
                }

                foreach ((array) $arguments as $argument) {
                    $finder->$method($argument);
                }
            }

            foreach ($finder as $file) {
                $absolute = $file->getRealPath();

                $files[$this->relativeOf($absolute)] = $absolute;
            }
        }

        if (isset($pwd)) {
            chdir($pwd);
        }

        if ($this['blacklist']) {
            foreach ((array) $this['blacklist'] as $relative) {
                unset($files[$relative]);
            }
        }

        return array_values($files);
    }

    /**
     * Returns the Git commit short hash.
     *
     * @return string The short hash.
     */
    public function getGitCommit()
    {
        $process = new Process('git log --pretty="%h" -n1 HEAD', $this['base-path']);

        if (0 === $process->run()) {
            return trim($process->getOutput());
        }
    }

    /**
     * Returns the Git commit tag.
     *
     * @return string The tag.
     */
    public function getGitTag()
    {
        $process = new Process('git describe --tags HEAD', $this['base-path']);

        if (0 === $process->run()) {
            return trim($process->getOutput());
        }
    }

    /** {@inheritDoc} */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /** {@inheritDoc} */
    public function getName()
    {
        return 'config';
    }

    /**
     * Loads the configuration file.
     *
     * @param string $file The configuration file path.
     *
     * @throws InvalidArgumentException If the data is invalid.
     */
    public function load($file)
    {
        $json = $this->getHelperSet()->get('json');

        $data = $json->parseFile($file);

        $json->validate($file, $data);

        if (false === empty($data['algorithm'])) {
            if (false === defined('Phar::' . $data['algorithm'])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid algorithm constant: Phar::%s',
                    $data['algorithm']
                ));
            }

            $data['algorithm'] = constant('Phar::' . $data['algorithm']);
        }

        if (false === empty($data['compression'])) {
            if (false === defined('Phar::' . $data['compression'])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid compression algorithm constant: Phar::%s',
                    $data['compression']
                ));
            }

            $data['compression'] = constant('Phar::' . $data['compression']);
        }

        $this->exchangeArray(array_merge($this->getArrayCopy(), $data));

        if (null === $this['base-path']) {
            $this['base-path'] = dirname($file);
        }

        if ($this['git-version']) {
            $version = $this->getGitTag() ?: $this->getGitCommit();

            $this['replacements'] = array_merge(
                array(
                    $this['git-version'] => $version
                ),
                $this['replacements']
            );
        }
    }

    /**
     * Returns the relative path of the absolute path.
     *
     * @param string $path The absolute path.
     *
     * @return string The relative path.
     */
    public function relativeOf($path)
    {
        return ltrim(str_replace($this['base-path'], '', $path), '\\/');
    }

    /** {@inheritDoc} */
    public function setHelperSet(HelperSet $helperSet = null)
    {
        $this->helperSet = $helperSet;
    }
}