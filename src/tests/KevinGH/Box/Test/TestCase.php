<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Test;

    use KevinGH\Box\Box,
        PHPUnit_Framework_TestCase,
        ReflectionMethod,
        ReflectionProperty,
        RuntimeException,
        Symfony\Component\Process\PhpProcess,
        Symfony\Component\Process\Process;

    /**
     * The resources directory path.
     *
     * @type string
     */
    define('RESOURCES', realpath(__DIR__ . '/../../../Resources') . '/');

    /**
     * A test case for the Box library.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class TestCase extends PHPUnit_Framework_TestCase
    {
        /**
         * The project temporary prefix.
         *
         * @type string
         */
        const PREFIX = 'box';

        /**
         * The app template.
         *
         * @type App
         */
        private $app;

        /**
         * The list of created directories and files.
         *
         * @type array
         */
        private $temporary = array();

        /**
         * Removes the created temporary paths.
         */
        protected function tearDown()
        {
            foreach ($this->temporary as $path)
            {
                $this->remove($path);
            }
        }

        /**
         * Executes an external command.
         *
         * @throws RuntimeException If the command fails.
         * @param string $command The command.
         * @param string $dir The working directory.
         * @return string The output.
         */
        public function command($command, $dir = null)
        {
            $process = new Process($command, $dir);

            if (0 !== $process->run())
            {
                throw new RuntimeException(
                    $process->getOutput() . $process->getErrorOutput()
                );
            }

            return trim($process->getOutput());
        }

        /**
         * Creates a private key.
         *
         * @param string|null $pass The passphrase.
         * @return string The new private key.
         */
        public function createPrivateKey($pass = null)
        {
            $resource = openssl_pkey_new();

            openssl_pkey_export($resource, $key, $pass);

            openssl_pkey_free($resource);

            return $key;
        }

        /**
         * Creates a public key from the private key.
         *
         * @param string The private key.
         * @param string|null The passphrase.
         * @return string The public key.
         */
        public function createPublicKey($private, $pass = null)
        {
            $resource = openssl_pkey_get_private($key, $pass);

            $details = openssl_pkey_get_details($resource);

            openssl_pkey_free($resource);

            return $details['key'];
        }

        /**
         * Creates a temporary directory.
         *
         * @return string The temporary directory path.
         */
        public function dir()
        {
            unlink($path = $this->file());

            mkdir($path);

            $this->temporary[] = $path;

            return $path;
        }

        /**
         * Creates a temporary file.
         *
         * @param mixed $content The file's new content.
         * @return string The temporary file path.
         */
        public function file($content = null)
        {
            $file = tempnam(sys_get_temp_dir(), self::PREFIX);

            if (null !== $content)
            {
                file_put_contents($file, $content);
            }

            $this->temporary[] = $file;

            return $file;
        }

        /**
         * Returns the app template.
         *
         * @param boolean $sign Sign the PHAR?
         * @param string $pass The private key passphrase.
         * @return App The app template.
         */
        protected function getApp($sign = false, $pass = null)
        {
            $box = __DIR__ . '/../../../../lib/KevinGH/Box/Box.php';
            $file = $this->dir() . '/default.phar';
            $main = RESOURCES . 'app/bin/main.php';
            $class = RESOURCES . 'app/src/lib/class.php';

            $process = <<<SOURCE
<?php

    use KevinGH\Box\Box;

    require '$box';

    \$box = new Box('$file');

    \$box->startBuffering();

    \$box->importFile('bin/main.php', '$main');
    \$box->importFile('src/lib/class.php', '$class');

    \$box->setStub(\$box->createStub());

    \$box->stopBuffering();

SOURCE
            ;

            if ($sign)
            {
                $key = $this->file($this->createPrivateKey($pass));

                $process .= <<<SOURCE

    \$box->usePrivateKeyFile('$key', '$pass');

SOURCE
                ;
            }

            $process = new PhpProcess($process);

            $process->run();

            if (false === file_exists($file))
            {
                throw new RuntimeException('The app could not be built.');
            }

            return $file;

            /*
            $box = new Box($file);

            $box->startBuffering();

            $box->importFile('bin/main.php', RESOURCES . 'app/bin/main.php', true);
            $box->importFile('src/lib/class.php', RESOURCES . 'app/src/lib/class.php');

            $box->setStub($box->createStub());

            $box->stopBuffering();

            if ($sign)
            {
                $pem = dirname($file) . '/test.pem';

                file_put_contents($pem, $this->createPrivateKey('phpunit'));

                $box->usePrivateKeyFile($pem, 'phpunit');
            }
            */

            return array($file, $box);
        }

        /**
         * Returns the method as ReflectionMethod.
         *
         * @param object $object The object.
         * @param string $name The method nam.
         * @return Closure The method.
         */
        public function method($object, $name)
        {
            $method = new ReflectionMethod($object, $name);

            $method->setAccessible(true);

            return function () use ($object, $method)
            {
                return $method->invokeArgs($object, func_get_args());
            };
        }

        /**
         * Returns the property as ReflectionProperty.
         *
         * @param object $object The object.
         * @param string $name The property name.
         * @return Closure The property.
         */
        public function property($object, $name)
        {
            $property = new ReflectionProperty($object, $name);

            $property->setAccessible(true);

            return function () use ($object, $property)
            {
                if (0 < func_num_args())
                {
                    $property->setValue($object, func_get_arg(0));
                }

                else
                {
                    return $property->getValue($object);
                }
            };
        }

        /**
         * Returns the contents of the resource file.
         *
         * @param string $file The resource file name.
         * @param boolean $return Return the file path?
         * @return string The file contents or path.
         */
        public function resource($file, $return = false)
        {
            if ($return)
            {
                return RESOURCES . $file;
            }

            return file_get_contents(RESOURCES . $file);
        }

        /**
         * Creates a new temporary tree.
         *
         * @param array $tree The tree.
         * @param string $root The root directory path.
         * @return string The root directory path.
         */
        public function tree(array $tree, $root = null)
        {
            if (null === $root)
            {
                $root = $this->dir();
            }

            foreach ($tree as $key => $value)
            {
                if (is_array($value))
                {
                    $keyPath = $root . DIRECTORY_SEPARATOR . $key;

                    if (false === is_dir($keyPath))
                    {
                        mkdir($keyPath);
                    }

                    $this->tree($value, $keyPath);
                }

                else
                {
                    touch($root . DIRECTORY_SEPARATOR . $value);
                }
            }

            return $root;
        }

        /**
         * Redefines a function.
         *
         * @param string $name The function name.
         * @param string $args The function arguments.
         * @param string $code The function code.
         */
        public function redefine($name, $args, $code)
        {
            runkit_function_rename($name, "_$name");

            runkit_function_add($name, $args, $code);
        }

        /**
         * Recursively removes the path.
         *
         * @param string $path The path to remove.
         */
        public function remove($path)
        {
            if ($path = realpath($path))
            {
                if (is_dir($path) && (false === is_link($path)))
                {
                    foreach (scandir($path) as $node)
                    {
                        if (in_array($node, array('.', '..')))
                        {
                            continue;
                        }

                        $nodePath = $path . DIRECTORY_SEPARATOR . $node;

                        $this->remove($nodePath);
                    }

                    rmdir($path);
                }

                else
                {
                    unlink($path);
                }
            }
        }

        /**
         * Restores the redefined function.
         *
         * @param string $name The function name.
         */
        public function restore($name)
        {
            runkit_function_remove($name);

            runkit_function_rename("_$name", $name);
        }
    }