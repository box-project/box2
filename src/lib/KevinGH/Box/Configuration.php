<?php

namespace KevinGH\Box;

use Herrera\Box\Compactor\CompactorInterface;
use InvalidArgumentException;
use Phar;
use Symfony\Component\Finder\Finder;

/**
 * Manages the configuration settings.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Configuration
{
    /**
     * The configuration file path.
     *
     * @var string
     */
    private $file;

    /**
     * The raw configuration settings.
     *
     * @var object
     */
    private $raw;

    /**
     * Sets the raw configuration settings.
     *
     * @param string $file The configuration file path.
     * @param object $raw  The raw settings.
     */
    public function __construct($file, $raw)
    {
        $this->file = $file;
        $this->raw = $raw;
    }

    /**
     * Returns the Phar alias.
     *
     * @return string The alias.
     */
    public function getAlias()
    {
        if (isset($this->raw->alias)) {
            return $this->raw->alias;
        }

        return 'default.phar';
    }

    /**
     * Returns the base path.
     *
     * @return string The base path.
     *
     * @throws InvalidArgumentException If the base path is not valid.
     */
    public function getBasePath()
    {
        if (isset($this->raw->{'base-path'})) {
            if (false === is_dir($this->raw->{'base-path'})) {
                throw new InvalidArgumentException(sprintf(
                    'The base path "%s" is not a directory or does not exist.',
                    $this->raw->{'base-path'}
                ));
            }

            return realpath($this->raw->{'base-path'});
        }

        return realpath(dirname($this->file));
    }

    /**
     * Returns the list of relative directory paths for binary files.
     *
     * @return array The list of paths.
     */
    public function getBinaryDirectories()
    {
        if (isset($this->raw->{'directories-bin'})) {
            return (array) $this->raw->{'directories-bin'};
        }

        return array();
    }

    /**
     * Returns the list of relative paths for binary files.
     *
     * @return array The list of paths.
     */
    public function getBinaryFiles()
    {
        if (isset($this->raw->{'files-bin'})) {
            return (array) $this->raw->{'files-bin'};
        }

        return array();
    }

    /**
     * Returns the list of configured Finder instances for binary files.
     *
     * @return Finder[] The list of Finders.
     */
    public function getBinaryFinders()
    {
        if (isset($this->raw->{'finder-bin'})) {
            return $this->processFinders($this->raw->{'finder-bin'});
        }

        return array();
    }

    /**
     * Returns the list of blacklisted relative file paths.
     *
     * @return array The list of paths.
     */
    public function getBlacklist()
    {
        if (isset($this->raw->blacklist)) {
            return (array) $this->raw->blacklist;
        }

        return array();
    }

    /**
     * Returns the list of file contents compactors.
     *
     * @return CompactorInterface[] The list of compactors.
     *
     * @throws InvalidArgumentException If a class is not valid.
     */
    public function getCompactors()
    {
        $compactors = array();

        if (isset($this->raw->compactors)) {
            foreach ($this->raw->compactors as $class) {
                if (false === class_exists($class)) {
                    throw new InvalidArgumentException(sprintf(
                        'The compactor class "%s" does not exist.',
                        $class
                    ));
                }

                $compactor = new $class();

                if (false === ($compactor instanceof CompactorInterface)) {
                    throw new InvalidArgumentException(sprintf(
                        'The class "%s" is not a compactor class.',
                        $class
                    ));
                }

                $compactors[] = $compactor;
            }
        }

        return $compactors;
    }

    /**
     * Returns the Phar compression algorithm.
     *
     * @return integer The compression algorithm.
     *
     * @throws InvalidArgumentException If the algorithm is not valid.
     */
    public function getCompressionAlgorithm()
    {
        if (isset($this->raw->compression)) {
            if (is_string($this->raw->compression)) {
                if (false === defined('Phar::' . $this->raw->compression)) {
                    throw new InvalidArgumentException(sprintf(
                        'The compression algorithm "%s" is not supported.',
                        $this->raw->compression
                    ));
                }

                return constant('Phar::' . $this->raw->compression);
            }

            return $this->raw->compression;
        }
    }

    /**
     * Returns the list of relative directory paths.
     *
     * @return array The list of paths.
     */
    public function getDirectories()
    {
        if (isset($this->raw->directories)) {
            return (array) $this->raw->directories;
        }

        return array();
    }

    /**
     * Returns the file mode in octal form.
     *
     * @return integer The file mode.
     */
    public function getFileMode()
    {
        if (isset($this->raw->chmod)) {
            return intval($this->raw->chmod, 8);
        }
    }

    /**
     * Returns the list of relative file paths.
     *
     * @return array The list of paths.
     */
    public function getFiles()
    {
        if (isset($this->raw->files)) {
            return (array) $this->raw->files;
        }

        return array();
    }

    /**
     * Returns the list of configured Finder instances.
     *
     * @return Finder[] The list of Finders.
     */
    public function getFinders()
    {
        if (isset($this->raw->finder)) {
            return $this->processFinders($this->raw->finder);
        }

        return array();
    }

    /**
     * Returns the git version placeholder.
     *
     * @return string The placeholder.
     */
    public function getGitVersionPlaceholder()
    {
        if (isset($this->raw->{'git-version'})) {
            return $this->raw->{'git-version'};
        }
    }

    /**
     * Returns the main script file path.
     *
     * @return string The file path.
     */
    public function getMainScriptPath()
    {
        if (isset($this->raw->main)) {
            return $this->raw->main;
        }
    }

    /**
     * Returns the Phar metadata.
     *
     * @return mixed The metadata.
     */
    public function getMetadata()
    {
        if (isset($this->raw->metadata)) {
            return $this->raw->metadata;
        }
    }

    /**
     * Returns the output file path.
     *
     * @return string The file path.
     */
    public function getOutputPath()
    {
        if (isset($this->raw->output)) {
            return $this->raw->output;
        }

        return 'default.phar';
    }

    /**
     * Returns the private key passphrase.
     *
     * @return string The passphrase.
     */
    public function getPrivateKeyPassphrase()
    {
        if (isset($this->raw->{'key-pass'})
            && is_string($this->raw->{'key-pass'})) {
            return $this->raw->{'key-pass'};
        }
    }

    /**
     * Returns the private key file path.
     *
     * @return string The file path.
     */
    public function getPrivateKeyPath()
    {
        if (isset($this->raw->key)) {
            return $this->raw->key;
        }
    }

    /**
     * Returns the list of replacement placeholders and their values.
     *
     * @return array The list of replacements.
     */
    public function getReplacements()
    {
        if (isset($this->raw->replacements)) {
            return (array) $this->raw->replacements;
        }

        return array();
    }

    /**
     * Returns the Phar signing algorithm.
     *
     * @return integer The signing algorithm.
     *
     * @throws InvalidArgumentException If the algorithm is not valid.
     */
    public function getSigningAlgorithm()
    {
        if (isset($this->raw->algorithm)) {
            if (is_string($this->raw->algorithm)) {
                if (false === defined('Phar::' . $this->raw->algorithm)) {
                    throw new InvalidArgumentException(sprintf(
                        'The signing algorithm "%s" is not supported.',
                        $this->raw->algorithm
                    ));
                }

                return constant('Phar::' . $this->raw->algorithm);
            }

            return $this->raw->algorithm;
        }

        return Phar::SHA1;
    }

    /**
     * Returns the Phar stub file path.
     *
     * @return string The file path.
     */
    public function getStubPath()
    {
        if (isset($this->raw->stub) && is_string($this->raw->stub)) {
            return $this->raw->stub;
        }
    }

    /**
     * Checks if Phar::interceptFileFuncs() should be used.
     *
     * @return boolean TRUE if it should be used, FALSE if not.
     */
    public function isInterceptFileFuncs()
    {
        if (isset($this->raw->intercept)) {
            return $this->raw->intercept;
        }

        return false;
    }

    /**
     * Checks if the user should be prompted for the private key passphrase.
     *
     * @return boolean TRUE if they should be prompted, FALSE if not.
     */
    public function isPrivateKeyPrompt()
    {
        if (isset($this->raw->{'key-pass'})
            && (true === $this->raw->{'key-pass'})) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the Phar stub should be generated.
     *
     * @return boolean TRUE if it should be generated, FALSE if not.
     */
    public function isStubGenerated()
    {
        if (isset($this->raw->stub) && (true === $this->raw->stub)) {
            return true;
        }

        return false;
    }

    /**
     * Processes the Finders configuration list.
     *
     * @param array $config The configuration.
     *
     * @return Finder[] The list of Finders.
     *
     * @throws InvalidArgumentException If the configured method does not exist.
     */
    private function processFinders(array $config)
    {
        $finders = array();

        foreach ($config as $methods) {
            $finder = Finder::create()->files()->ignoreVCS(true);

            foreach ($methods as $method => $arguments) {
                if (false === method_exists($finder, $method)) {
                    throw new InvalidArgumentException(sprintf(
                        'The method "Finder::%s" does not exist.',
                        $method
                    ));
                }

                foreach ((array) $arguments as $argument) {
                    $finder->$method($argument);
                }
            }

            $finders[] = $finder;
        }

        return $finders;
    }
}