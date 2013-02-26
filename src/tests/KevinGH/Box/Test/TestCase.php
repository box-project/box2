<?php

/* This file is part of Elf.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Test;

use PHPUnit_Framework_TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;

/**
 * The resources directory path.
 *
 * @var string
 */
define('RESOURCE_PATH', realpath(__DIR__ . '/../../../../../res'));

/**
 * Provides simple methods for doing common things.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * The temporary file name prefix.
     *
     * @var string
     */
    const PREFIX = 'box';

    /**
     * The current working directory.
     *
     * @var string
     */
    protected $currentDir;

    /**
     * The former current working directory.
     *
     * @var string
     */
    protected $formerDir;

    /**
     * The redeclared functions.
     *
     * @var array
     */
     protected static $redeclared = array();

    /**
     * The streams.
     *
     * @var array
     */
    private static $streams = array();

    /**
     * The temporary paths.
     *
     * @var array
     */
    protected static $temporary = array();

    /**
     * Removes all created temporary paths and restores redeclared functions.
     */
    protected function setUp()
    {
        foreach (self::$redeclared as $function) {
            $this->restore($function);
        }

        foreach (self::$streams as $stream) {
            fclose($stream);
        }

        foreach (self::$temporary as $path) {
            $this->remove($path);
        }

        self::$redeclared = array();
        self::$streams = array();
        self::$temporary = array();

        $this->formerDir = getcwd();
        $this->currentDir = $this->dir();

        chdir($this->currentDir);
    }

    /**
     * Removes all created temporary paths and restores redeclared functions.
     */
    protected function tearDown()
    {
        chdir($this->formerDir);

        foreach (self::$redeclared as $function) {
            $this->restore($function);
        }

        foreach (self::$streams as $stream) {
            fclose($stream);
        }

        foreach (self::$temporary as $path) {
            $this->remove($path);
        }

        self::$redeclared = array();
        self::$streams = array();
        self::$temporary = array();
    }

    /**
     * Checks for support from an extension.
     *
     * @param PHPUnit_Framework_TestCase $test      The test case.
     * @param string                     $extension The extension name.
     *
     * @return boolean TRUE if not supported, FALSE if supported.
     */
    public function checkSupport(PHPUnit_Framework_TestCase $test, $extension)
    {
        if (extension_loaded($extension)) {
            return false;
        }

        $test->markTestSkipped("The extension \"$extension\" is not available.");

        return true;
    }

    /**
     * Executes an external command.
     *
     * @param string  $command The command.
     * @param string  $dir     The working directory.
     * @param boolean $throw   Throw an exception on error?
     *
     * @return string The output.
     *
     * @throws RuntimeException If the command fails.
     */
    public function command($command, $dir = null, $throw = true)
    {
        $process = new Process($command, $dir);

        if (0 === $process->run()) {
            return trim($process->getOutput() . $process->getErrorOutput());
        } elseif ($throw) {
            throw new RuntimeException($process->getOutput() . $process->getErrorOutput());
        }
    }

    /**
     * Recursively copies a directory.
     *
     * @param string $source The source directory.
     * @param string $target The target directory.
     *
     * @throws InvalidArgumentException If either directory does not exist.
     * @throws RuntimeException         If either path could not be read or created.
     */
    public function copy($source, $target)
    {
        if (false === is_dir($source)) {
            throw new InvalidArgumentException(sprintf(
                'The directory path "%s" does not exist.',
                $source
            ));
        }

        if (false === is_dir($target)) {
            if (false === @ mkdir($target, 0755, true)) {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The directory path "%s" could not be created: %s',
                    $error['message']
                ));
            }
        }

        $source = realpath($source);
        $target = realpath($target);

        foreach (scandir($source) as $node) {
            if (('.' == $node) || ('..' == $node)) {
                continue;
            }

            $path = $source . DIRECTORY_SEPARATOR . $node;
            $new = $target . DIRECTORY_SEPARATOR . $node;

            if (is_dir($path)) {
                $this->copy($path, $new);
            } else {
                if (false === @ copy($path, $new)) {
                    $error = error_get_last();

                    throw new RuntimeException(sprintf(
                        'Unable to copy file "%s" to "%s": %s',
                        $path,
                        $new,
                        $error['message']
                    ));
                }
            }
        }
    }

    /**
     * Creates a memory stream.
     *
     * @param string  $mode    The mode.
     * @param boolean $symfony Create a Symfony Console output stream?
     * @param boolean $verbose Is verbose stream?
     *
     * @return resource|StreamOutput The memory stream.
     */
    public function createStream($mode = 'w', $symfony = false, $verbose = false)
    {
        $stream = self::$streams[] = fopen('php://memory', $mode, false);

        if ($symfony) {
            $stream = new StreamOutput(
                $stream,
                $verbose ? StreamOutput::VERBOSITY_VERBOSE : StreamOutput::VERBOSITY_NORMAL,
                false
            );
        }

        return $stream;
    }

    /**
     * Creates a temporary directory.
     *
     * @param string $dir The base directory path. 
     *
     * @return string The temporary directory path.
     */
    public function dir($dir = null)
    {
        unlink($path = $this->file(null, $dir));

        mkdir($path);

        self::$temporary[] = $path;

        return $path;
    }

    /**
     * Creates a temporary file.
     *
     * @param mixed  $content The file's new content.
     * @param string $dir     The base directory path. 
     *
     * @return string The temporary file path.
     */
    public function file($content = null, $dir = null)
    {
        if (null === $dir) {
            $dir = sys_get_temp_dir();
        }

        if (false === ($file = tempnam($dir, self::PREFIX))) {
            throw new RuntimeException('Could not create temporary file.');
        }

        if (null !== $content) {
            file_put_contents($file, $content);
        }

        self::$temporary[] = $file;

        // runkit sanity check
        if (null !== $content) {
            if ($content !== file_get_contents($file)) {
                throw new RuntimeException(sprintf(
                    'The file "%s" could not be written.',
                    $file
                ));
            }
        }

        return $file;
    }

    /**
     * Returns the resource file path or contents.
     *
     * @param string  $name     The relative resource name.
     * @param boolean $contents Return the resource's contents?
     *
     * @return string The file path or contents.
     */
    public function getResource($name, $contents = false)
    {
        $path = RESOURCE_PATH . DIRECTORY_SEPARATOR . $name;

        if ($contents) {
            return file_get_contents($path);
        }

        return $path;
    }

    /**
     * Returns the stream's contents.
     *
     * @param resource The stream.
     *
     * @return string The contents.
     */
    public function getStreamContents($stream)
    {
        rewind($stream);

        return stream_get_contents($stream);
    }

    /**
     * Returns the class method as a closure.
     *
     * @param object $object The class object.
     * @param string $method The method name.
     *
     * @return Closure The method closure.
     *
     * @throws RuntimeException If the method does not exist.
     */
    public function method($object, $method)
    {
        $class = new ReflectionClass($object);

        while (false === $class->hasMethod($method)) {
            if (null === ($parent = $class->getParentClass())) {
                throw new RuntimeException(sprintf(
                    'The class "%s" does not have the method "%s".',
                    get_class($object),
                    $method
                ));
            }

            $class = $parent;
        }

        $method = $class->getMethod($method);

        $method->setAccessible(true);

        if ($method->isStatic()) {
            $object = null;
        }

        return function () use ($object, $method) {
            return $method->invokeArgs($object, func_get_args());
        };
    }

    /**
     * Returns the class property as a closure.
     *
     * @param object $object   The class object.
     * @param string $property The property name.
     *
     * @return Closure The property closure.
     *
     * @throws RuntimeException If the property does not exist.
     */
    public function property($object, $property)
    {
        $class = new ReflectionClass($object);

        while (false === $class->hasProperty($property)) {
            if (null === ($parent = $class->getParentClass())) {
                throw new RuntimeException(sprintf(
                    'The class "%s" does not have the property "%s".',
                    get_class($object),
                    $property
                ));
            }

            $class = $parent;
        }

        $property = $class->getProperty($property);

        $property->setAccessible(true);

        if ($property->isStatic()) {
            $object = null;
        }

        return function () use ($object, $property) {
            if (func_num_args()) {
                $property->setValue($object, func_get_arg(0));
            }

            return $property->getValue($object);
        };
    }

    /**
     * Redeclares an existing function.
     *
     * @param PHPUnit_Framework_TestCase $test The test case.
     * @param string                     $name The function name.
     * @param string                     $args The function arguments.
     * @param string                     $code The function code.
     *
     * @return boolean TRUE if runkit is not available.
     */
    public function redeclare($test, $name, $args, $code)
    {
        if (false === extension_loaded('runkit')) {
            $test->markTestSkipped('The "runkit" extension is not available.');

            return true;
        }

        runkit_function_rename($name, "_$name");
        runkit_function_add($name, $args, $code);

        self::$redeclared[] = $name;
    }

    /**
     * Recursively removes the path.
     *
     * @param string $path The path to remove.
     */
    public function remove($path)
    {
        if ($path = realpath($path)) {
            if (is_dir($path) && (false === is_link($path))) {
                foreach (scandir($path) as $node) {
                    if (in_array($node, array(
                        '.',
                        '..'
                    ))) {
                        continue;
                    }

                    $nodePath = $path . DIRECTORY_SEPARATOR . $node;

                    $this->remove($nodePath);
                }

                rmdir($path);
            } else {
                unlink($path);
            }
        }
    }

    /**
     * Restores the redeclared function.
     *
     * @param string $name The function name.
     */
    public function restore($name)
    {
        runkit_function_remove($name);
        runkit_function_rename("_$name", $name);
    }
}

