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

    use ArrayObject,
        InvalidArgumentException,
        KevinGH\Box\Console\Exception\JSONException,
        Phar,
        RuntimeException,
        Symfony\Component\Console\Helper\HelperInterface,
        Symfony\Component\Console\Helper\HelperSet,
        Symfony\Component\Finder\Finder,
        Symfony\Component\Process\Process;

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
         */
        public function __construct(array $input = array())
        {
            parent::__construct(array_merge(
                array(
                    'algorithm' => Phar::SHA1,
                    'alias' => 'default.phar',
                    'base-path' => null,
                    'directories' => array(),
                    'files' => array(),
                    'finder' => array(),
                    'git-version' => null,
                    'key' => null,
                    'key-pass' => null,
                    'main' => null,
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
         * @throws InvalidArgumentException If the file does not exist.
         * @param string $file The optional file path.
         * @return string The found configuration file path.
         */
        public function find($file)
        {
            if ($file)
            {
                if (false === file_exists($file))
                {
                    throw new InvalidArgumentException('The configuration file does not exist.');
                }

                $file = realpath($file);
            }

            elseif (file_exists('box.json'))
            {
                $file = realpath('box.json');
            }

            elseif (file_exists('box-dist.json'))
            {
                $file = realpath('box-dist.json');
            }

            else
            {
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
            if (false === ($cwd = getcwd()))
            {
                if (isset($_SERVER['PWD']))
                {
                    $cwd = $_SERVER['PWD'];
                }

                else
                {
                    throw new RuntimeException('Could not get current working directory path.');
                }
            }

            return $cwd;
        }

        /**
         * Returns a list of files found using the configuration.
         *
         * @throws InvalidArgumentException If a path is not a file.
         * @return array The list of files.
         */
        public function getFiles()
        {
            $files = array();

            if (is_dir($this['base-path']))
            {
                $pwd = $this->getCurrentDir();

                chdir($this['base-path']);
            }

            foreach ((array) $this['files'] as $file)
            {
                if (false === is_file($file))
                {
                    throw new InvalidArgumentException(sprintf(
                        'The path is not a file: %s',
                        $file
                    ));
                }

                $files[] = realpath($file);
            }

            foreach ((array) $this['directories'] as $dir)
            {
                $finder = new Finder;

                $finder->files()
                       ->ignoreVCS(true)
                       ->name('*.php')
                       ->in($dir);

                foreach ($finder as $file)
                {
                    $files[] = $file->getRealPath();
                }
            }

            foreach ((array) $this['finder'] as $methods)
            {
                $finder = new Finder;

                $finder->files()->ignoreVCS(true);

                foreach ($methods as $method => $arguments)
                {
                    if (false === method_exists($finder, $method))
                    {
                        throw new InvalidArgumentException(sprintf(
                            'Invalid Finder setting: %s',
                            $method
                        ));
                    }

                    foreach ((array) $arguments as $argument)
                    {
                        $finder->$method($argument);
                    }
                }

                foreach ($finder as $file)
                {
                    $files[] = $file->getRealPath();
                }
            }

            if (isset($pwd))
            {
                chdir($pwd);
            }

            return $files;
        }

        /**
         * Returns the Git commit short hash.
         *
         * @return string The short hash.
         */
        public function getGitCommit()
        {
            $process = new Process('git log --pretty="%h" -n1 HEAD', $this['base-path']);

            if (0 === $process->run())
            {
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

            if (0 === $process->run())
            {
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
         * @throws RuntimeException If the data could not be loaded.
         * @param string $file The configuration file path.
         */
        public function load($file)
        {
            if (false === ($data = @ file_get_contents($file)))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The configuration file could not be read: %s',
                    $error['message']
                ));
            }

            if (null === ($data = json_decode($data, true)))
            {
                if (JSON_ERROR_NONE !== ($code = json_last_error()))
                {
                    throw new JSONException($code);
                }

                $data = array();
            }

            if (false === empty($data['algorithm']))
            {
                if (false === defined('Phar::' . $data['algorithm']))
                {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid algorithm constant: Phar::%s',
                        $data['algorithm']
                    ));
                }

                $data['algorithm'] = constant('Phar::' . $data['algorithm']);
            }

            $this->exchangeArray(array_merge($this->getArrayCopy(), $data));

            if (null === $this['base-path'])
            {
                $this['base-path'] = dirname($file);
            }

            if ($this['git-version'])
            {
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