<?php

namespace KevinGH\Box;

use ArrayIterator;
use Herrera\Box\Compactor\CompactorInterface;
use InvalidArgumentException;
use Phar;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

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
     * Returns the base path as a regular expression for trimming paths.
     *
     * @return string The regular expression.
     */
    public function getBasePathRegex()
    {
        return '/'
             . preg_quote($this->getBasePath() . DIRECTORY_SEPARATOR, '/')
             . '/';
    }

    /**
     * Returns the list of relative directory paths for binary files.
     *
     * @return array The list of paths.
     */
    public function getBinaryDirectories()
    {
        if (isset($this->raw->{'directories-bin'})) {
            $directories = (array) $this->raw->{'directories-bin'};
            $base = $this->getBasePath();

            array_walk($directories, function (&$directory) use ($base) {
                $directory = $base
                           . DIRECTORY_SEPARATOR
                           . canonical_path($directory);
            });

            return $directories;
        }

        return array();
    }

    /**
     * Returns the iterator for the binary directory paths.
     *
     * @return Finder The iterator.
     */
    public function getBinaryDirectoriesIterator()
    {
        if (array() !== ($directories = $this->getBinaryDirectories())) {
            return Finder::create()
                    ->files()
                    ->filter($this->getBlacklistFilter())
                    ->ignoreVCS(true)
                    ->in($directories);
        }
    }

    /**
     * Returns the list of relative paths for binary files.
     *
     * @return array The list of paths.
     */
    public function getBinaryFiles()
    {
        if (isset($this->raw->{'files-bin'})) {
            $base = $this->getBasePath();
            $files = array();

            foreach ((array) $this->raw->{'files-bin'} as $file) {
                $files[] = new SplFileInfo(
                    $base . DIRECTORY_SEPARATOR . canonical_path($file)
                );
            }

            return $files;
        }

        return array();
    }

    /**
     * Returns an iterator for the binary files.
     *
     * @return ArrayIterator The iterator.
     */
    public function getBinaryFilesIterator()
    {
        if (array() !== ($files = $this->getBinaryFiles())) {
            return new ArrayIterator($files);
        }
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
            $blacklist = (array) $this->raw->blacklist;

            array_walk($blacklist, function (&$file) {
                $file = canonical_path($file);
            });

            return $blacklist;
        }

        return array();
    }

    /**
     * Returns a filter callable for the configured blacklist.
     *
     * @return callable The callable.
     */
    public function getBlacklistFilter()
    {
        $blacklist = $this->getBlacklist();
        $base = '/^'
              . preg_quote($this->getBasePath()
              . DIRECTORY_SEPARATOR, '/')
              . '/';

        return function (SplFileInfo $file) use ($base, $blacklist) {
            $path = canonical_path(preg_replace(
                $base,
                '',
                $file->getPathname()
            ));

            if (in_array($path, $blacklist)) {
                return false;
            }
        };
    }

    /**
     * Returns the bootstrap file path.
     *
     * @return string The file path.
     */
    public function getBootstrapFile()
    {
        if (isset($this->raw->bootstrap)) {
            $path = $this->raw->bootstrap;

            if (false === is_absolute_path($path)) {
                $path = canonical_path(
                    $this->getBasePath()
                        . DIRECTORY_SEPARATOR
                        . $path
                );
            }

            return $path;
        }
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
            foreach ((array) $this->raw->compactors as $class) {
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
            $directories = (array) $this->raw->directories;
            $base = $this->getBasePath();

            array_walk($directories, function (&$directory) use ($base) {
                $directory = $base
                           . DIRECTORY_SEPARATOR
                           . canonical_path($directory);
            });

            return $directories;
        }

        return array();
    }

    /**
     * Returns the iterator for the directory paths.
     *
     * @return Finder The iterator.
     */
    public function getDirectoriesIterator()
    {
        if (array() !== ($directories = $this->getDirectories())) {
            return Finder::create()
                    ->files()
                    ->filter($this->getBlacklistFilter())
                    ->ignoreVCS(true)
                    ->in($directories);
        }
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
            $base = $this->getBasePath();
            $files = array();

            foreach ((array) $this->raw->files as $file) {
                $files[] = new SplFileInfo(
                    $base . DIRECTORY_SEPARATOR . canonical_path($file)
                );
            }

            return $files;
        }

        return array();
    }

    /**
     * Returns an iterator for the files.
     *
     * @return ArrayIterator The iterator.
     */
    public function getFilesIterator()
    {
        if (array() !== ($files = $this->getFiles())) {
            return new ArrayIterator($files);
        }
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
     * Returns the Git tag name or short commit hash.
     *
     * @return string The tag name or short commit hash.
     *
     * @throws RuntimeException If the version could not be retrieved.
     */
    public function getGitVersion()
    {
        $path = dirname($this->file);
        $process = new Process('git describe --tags HEAD', $path);

        if (0 === $process->run()) {
            return trim($process->getOutput());
        }

        $process = new Process('git log --pretty="%h" -n1 HEAD', $path);

        if (0 === $process->run()) {
            return trim($process->getOutput());
        }

        throw new RuntimeException(sprintf(
            'The tag or commit hash could not be retrieved from "%s": %s',
            $path,
            $process->getErrorOutput()
        ));
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
     * Returns the processed contents of the main script file.
     *
     * @return string The contents.
     *
     * @throws RuntimeException If the file could not be read.
     */
    public function getMainScriptContents()
    {
        if (null !== ($path = $this->getMainScriptPath())) {
            $path = $this->getBasePath() . DIRECTORY_SEPARATOR . $path;

            if (false === ($contents = @file_get_contents($path))){
                $errors = error_get_last();

                throw new RuntimeException($errors['message']);
            }

            return preg_replace('/^#!.*\s*/', '', $contents);
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
            if (is_object($this->raw->metadata)) {
                return (array) $this->raw->metadata;
            }

            return $this->raw->metadata;
        }
    }

    /**
     * Returns the file extension MIME type mapping.
     *
     * @return array The mapping.
     */
    public function getMimetypeMapping()
    {
        if (isset($this->raw->mimetypes)) {
            return (array) $this->raw->mimetypes;
        }

        return array();
    }

    /**
     * Returns the list of server variables to modify for execution.
     *
     * @return array The list of variables.
     */
    public function getMungVariables()
    {
        if (isset($this->raw->mung)) {
            return (array) $this->raw->mung;
        }

        return array();
    }

    /**
     * Returns the file path to the script to execute when a file is not found.
     *
     * @return string The file path.
     */
    public function getNotFoundScriptPath()
    {
        if (isset($this->raw->{'not-found'})) {
            return $this->raw->{'not-found'};
        }
    }

    /**
     * Returns the output file path.
     *
     * @return string The file path.
     */
    public function getOutputPath()
    {
        $base = getcwd() . DIRECTORY_SEPARATOR;

        if (isset($this->raw->output)) {
            $path = $this->raw->output;

            if (false === is_absolute_path($path)) {
                $path = canonical_path($base . $path);
            }

            return $path;
        }

        return $base . 'default.phar';
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
     * Returns the processed list of replacement placeholders and their values.
     *
     * @return array The list of replacements.
     */
    public function getProcessedReplacements()
    {
        $values = $this->getReplacements();

        if (null !== ($git = $this->getGitVersionPlaceholder())) {
            $values[$git] = $this->getGitVersion();
        }

        foreach ($values as $key => $value) {
            unset($values[$key]);

            $values["@$key@"] = $value;
        }

        return $values;
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
     * Checks if the Phar is going to be used for the web.
     *
     * @return boolean TRUE if it will be, FALSE if not.
     */
    public function isWebPhar()
    {
        if (isset($this->raw->web)) {
            return $this->raw->web;
        }

        return false;
    }

    /**
     * Loads the configured bootstrap file if available.
     */
    public function loadBootstrap()
    {
        if (null !== ($file = $this->getBootstrapFile())) {
            if (false === file_exists($file)) {
                throw new InvalidArgumentException(sprintf(
                    'The bootstrap path "%s" is not a file or does not exist.',
                    $file
                ));
            }

            include $file;
        }
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
        $filter = $this->getBlacklistFilter();

        foreach ($config as $methods) {
            $finder = Finder::create()
                        ->files()
                        ->filter($filter)
                        ->ignoreVCS(true);

            if (isset($methods->in)) {
                $base = $this->getBasePath();
                $methods->in = (array) $methods->in;

                array_walk($methods->in, function (&$directory) use ($base) {
                    $directory = $base . DIRECTORY_SEPARATOR . $directory;
                });
            }

            foreach ($methods as $method => $arguments) {
                if (false === method_exists($finder, $method)) {
                    throw new InvalidArgumentException(sprintf(
                        'The method "Finder::%s" does not exist.',
                        $method
                    ));
                }

                $arguments = (array) $arguments;

                foreach ($arguments as $argument) {
                    $finder->$method($argument);
                }
            }

            $finders[] = $finder;
        }

        return $finders;
    }
}