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

use KevinGH\Box\Console\Configuration;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides common functionality for the Box commands.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Box extends Helper
{
    /**
     * The output stream.
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * Chmods the PHAR archive(s).
     *
     * @param string         $file The PHAR file path.
     * @param integer|string $mode The file mode.
     */
    public function chmodPhar($file, $mode)
    {
        if (is_string($mode)) {
            $mode = intval($mode, 8);
        }

        foreach (array('', '.bz2', '.gz', '.tar', '.zip') as $extension) {
            if (file_exists($file . $extension)) {
                chmod($file . $extension, $mode);
            }
        }
    }

    /**
     * Finds the configuration file and returns a Configuration instance.
     *
     * @param null|string $alternative An alternative configuration file path.
     *
     * @return Configuration A Configuration instance.
     */
    public function find($alternative = null)
    {
        if (is_file($alternative)) {
            return Configuration::load($this->getHelperSet(), $alternative);
        } elseif (file_exists('box.json')) {
            return Configuration::load($this->getHelperSet(), 'box.json');
        } elseif (file_exists('box.dist.json')) {
            return Configuration::load($this->getHelperSet(), 'box.dist.json');
        }

        throw new RuntimeException('The configuration could not be found.');
    }

    /**
     * Returns the current working directory path.
     *
     * @return string The directory path.
     *
     * @throws RuntimeException If the directory path could not be found.
     */
    public function getCurrentDir()
    {
        if ($cwd = getcwd()) {
            return $cwd;
        }

        foreach (array('CD', 'PWD') as $variable) {
            if (isset($_SERVER[$variable])) {
                return $_SERVER[$variable];
            }
        }

        throw new RuntimeException('The current working directory path could not be found.');
    }

    /** {@inheritDoc} */
    public function getName()
    {
        return 'box';
    }

    /**
     * Checks if the output stream has its verbosity set to verbose.
     *
     * @param OutputInterface $output The output stream.
     *
     * @return boolean TRUE if verbose, FALSE if not.
     */
    public function isVerbose(OutputInterface $output)
    {
        return (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity());
    }

    /**
     * Prints a line with a pretty prefix.
     *
     * @param string  $prefix  The prefix string.
     * @param string  $message The message.
     * @param boolean $verbose Is the message verbose?
     */
    public function putln($prefix, $message, $verbose = false)
    {
        if ((false === $verbose) || ($verbose && $this->isVerbose($this->output))) {
            if ($prefix) {
                $this->output->write("<prefix>$prefix</prefix> ");
            }

            $this->output->writeln($message);
        }
    }

    /**
     * Removes the PHAR and its associated compressed versions.
     *
     * @param string $file The PHAR file path.
     */
    public function removePhar($file)
    {
        foreach (array('', '.bz2', '.gz', '.tar', '.zip') as $extension) {
            if (file_exists($file . $extension)) {
                unlink($file . $extension);
            }
        }
    }

    /**
     * Sets the output stream.
     *
     * @param OutputInterface $output The output stream.
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
}

