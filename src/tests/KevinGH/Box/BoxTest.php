<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box;

    use Exception,
        Phar,
        PHPUnit_Framework_TestCase,
        Symfony\Component\Process\Process;

    class BoxTest extends PHPUnit_Framework_TestCase
    {
        private $file;

        protected function setUp()
        {
            $this->file = tempnam(sys_get_temp_dir(), 'bxt');

            unlink($this->file);

            $this->file .= '.phar';
        }

        protected function tearDown()
        {
            if (file_exists($this->file))
            {
                unlink($this->file);
            }

            if (file_exists($this->file . '.pubkey'))
            {
                unlink($this->file . '.pubkey');
            }
        }

        public function testCompactSource()
        {
            $box = new Box($this->file, 0, 'test.phar');

            $source = <<<SOURCE
<?php

    /**
     * This is a test class.
     *
     * @author Testy McTesterson <test@cles.com>
     */
    class Test
    {
        private \$test;

        public function testMethod()
        {
            if (func_num_args() > 0)
            {
                \$this->test = func_get_arg(0);
            }

            return \$this->test;
        }
    }
SOURCE
            ;

            $box->setCompactor(function($source)
            {
                return $source .= "\ncustomer compactor called";
            });

            $result = $box->compactSource($source);

            $expected = <<<SOURCE
<?php






class Test
{
private \$test;

public function testMethod()
{
if (func_num_args() > 0)
{
\$this->test = func_get_arg(0);
}

return \$this->test;
}
}
customer compactor called
SOURCE
            ;

            $this->assertEquals($expected, $result);
        }

        public function testCreateStub()
        {
            $box = new Box($this->file);

            $expected = <<<STUB
#!/usr/bin/env php
<?php

    /**
     * Genereated by Box: http://github.com/kherge/Box
     */

    Phar::mapPhar('default.phar');

    __HALT_COMPILER();
STUB
            ;

            $this->assertEquals($expected, $box->createStub());
        }

        public function testCreateStubAlias()
        {
            $box = new Box($this->file, 0, 'wakka.phar');

            $expected = <<<STUB
#!/usr/bin/env php
<?php

    /**
     * Genereated by Box: http://github.com/kherge/Box
     */

    Phar::mapPhar('wakka.phar');

    __HALT_COMPILER();
STUB
            ;

            $this->assertEquals($expected, $box->createStub());
        }

        public function testCreateStubWithMain()
        {
            $box = new Box($this->file, 0, 'wakka.phar');

            $box->importSource('test/main.php', '<?php mainCode();', true);

            $expected = <<<STUB
#!/usr/bin/env php
<?php

    /**
     * Genereated by Box: http://github.com/kherge/Box
     */

    Phar::mapPhar('wakka.phar');

    require 'phar://wakka.phar/test/main.php';

    __HALT_COMPILER();
STUB
            ;

            $this->assertEquals($expected, $box->createStub());
        }

        public function testDoReplacements()
        {
            $box = new Box($this->file);

            $box->setReplacements(array(
                'test_value' => 'The actual test value.'
            ));

            $source = <<<SOURCE
<?php

    class Test
    {
        private \$test = '@test_value@';

        public function testMethod()
        {
            if (func_num_args() > 0)
            {
                \$this->test = func_get_arg(0);
            }

            return \$this->test;
        }
    }
SOURCE
            ;

            $expected = <<<SOURCE
<?php

    class Test
    {
        private \$test = 'The actual test value.';

        public function testMethod()
        {
            if (func_num_args() > 0)
            {
                \$this->test = func_get_arg(0);
            }

            return \$this->test;
        }
    }
SOURCE
            ;

            $this->assertEquals($expected, $box->doReplacements($source));
        }

        /**
         * @depends testCompactSource
         * @depends testCreateStub
         * @depends testCreateStubAlias
         * @depends testCreateStubWithMain
         * @depends testDoReplacements
         */
        public function testImport()
        {
            $box = new Box($this->file);

            $main = tempnam(sys_get_temp_dir(), 'bxt');
            $lib = tempnam(sys_get_temp_dir(), 'bxt');

            file_put_contents($main, <<<PHP
#!/usr/bin/php env
<?php

    require 'lib/test.php';

    TestLib::run();
PHP
            );

            file_put_contents($lib, <<<PHP
<?php

    class TestLib
    {
        public static function run()
        {
            echo "Success: @test@!\n";
        }
    }
PHP
            );

            $box->setReplacements(array('test' => 1234567890));
            $box->startBuffering();
            $box->importFile('main.php', $main, true);
            $box->importFile('lib/test.php', $lib);
            $box->setStub($box->createStub());
            $box->stopBuffering();

            unset($box);

            unlink($main);
            unlink($lib);

            $process = new Process('php ' . escapeshellarg($this->file));

            $this->assertFileExists($this->file);
            $this->assertEquals(0, $process->run());
            $this->assertEquals("Success: 1234567890!\n", $process->getOutput());
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The file does not exist: /does/not/exist
         */
        public function testImportFileInvalid()
        {
            $box = new Box($this->file);

            $box->importFile('not/exist', '/does/not/exist');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The file could not be read:
         */
        public function testImportFileReadError()
        {
            $box = new Box($this->file);

            $box->importFile('root', '/root');
        }

        public function testUsePrivateKeyFile()
        {
            if (false === extension_loaded('openssl'))
            {
                $this->markTestSkipped('The openssl extension is not available.');

                return;
            }

            $box = new Box($this->file);

            $key = tempnam(sys_get_temp_dir(), 'bxt');
            $main = tempnam(sys_get_temp_dir(), 'bxt');
            $lib = tempnam(sys_get_temp_dir(), 'bxt');

            $this->createKey($key, $pass = 'phpunit');

            file_put_contents($main, <<<PHP
#!/usr/bin/php env
<?php

    require 'lib/test.php';

    TestLib::run();
PHP
            );

            file_put_contents($lib, <<<PHP
<?php

    class TestLib
    {
        public static function run()
        {
            echo "Success: @test@!\n";
        }
    }
PHP
            );

            $box->setReplacements(array('test' => 1234567890));
            $box->startBuffering();
            $box->importFile('main.php', $main, true);
            $box->importFile('lib/test.php', $lib);
            $box->setStub($box->createStub());
            $box->stopBuffering();
            $box->usePrivateKeyFile($key, $pass);

            unset($box);

            unlink($key);
            unlink($main);
            unlink($lib);

            $process = new Process('php ' . escapeshellarg($this->file));

            $this->assertFileExists($this->file);
            $this->assertEquals(0, $process->run());
            $this->assertEquals("Success: 1234567890!\n", $process->getOutput());

            $phar = new Phar($this->file);

            $signature = $phar->getSignature();

            $this->assertEquals('OpenSSL', $signature['hash_type']);
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The private key file does not exist.
         */
        public function testUsePrivateKeyFileInvalid()
        {
            $box = new Box($this->file);

            $box->usePrivateKeyFile('/does/not/exist');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The "openssl" extension is not available.
         */
        public function testUsePrivateKeyFileExtensionNotLoaded()
        {
            if (extension_loaded('runkit'))
            {
                $box = new Box($this->file);

                touch($this->file);

                $this->redefine('extension_loaded', '', 'return false;');

                try
                {
                    $box->usePrivateKeyFile($this->file);
                }

                catch (Exception $exception)
                {
                }

                $this->restore('extension_loaded');

                if (isset($exception))
                {
                    throw $exception;
                }
            }

            else
            {
                $this->markTestSkipped('The runkit extension is not available.');
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The private key file could not be read:
         */
        public function testUsePrivateKeyFileReadError()
        {
            if (extension_loaded('runkit'))
            {
                $box = new Box($this->file);

                touch($this->file);

                $this->redefine('file_get_contents', '', 'return false;');

                try
                {
                    $box->usePrivateKeyFile($this->file);
                }

                catch (Exception $exception)
                {
                }

                $this->restore('file_get_contents');

                if (isset($exception))
                {
                    throw $exception;
                }
            }

            else
            {
                $this->markTestSkipped('The runkit extension is not available.');
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The public key could not be written:
         */
        public function testUsePrivateKeyFileWriteError()
        {
            if (extension_loaded('runkit'))
            {
                $box = new Box($this->file);

                $key = tempnam(sys_get_temp_dir(), 'bxt');

                $this->createKey($key);

                $this->redefine('file_put_contents', '', 'return false;');

                try
                {
                    $box->usePrivateKeyFile($key);
                }

                catch (Exception $exception)
                {
                }

                $this->restore('file_put_contents');

                unlink($key);

                if (isset($exception))
                {
                    throw $exception;
                }
            }

            else
            {
                $this->markTestSkipped('The runkit extension is not available.');
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The private key could not be parsed:
         */
        public function testUsePrivateKeyFileParseError()
        {
            if (extension_loaded('runkit'))
            {
                $box = new Box($this->file);

                $key = tempnam(sys_get_temp_dir(), 'bxt');

                $this->createKey($key);

                $this->redefine('openssl_pkey_get_private', '', 'return false;');

                try
                {
                    $box->usePrivateKeyFile($key);
                }

                catch (Exception $exception)
                {
                }

                $this->restore('openssl_pkey_get_private');

                unlink($key);

                if (isset($exception))
                {
                    throw $exception;
                }
            }

            else
            {
                $this->markTestSkipped('The runkit extension is not available.');
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The private key could not be exported:
         */
        public function testUsePrivateKeyFileExportError()
        {
            if (extension_loaded('runkit'))
            {
                $box = new Box($this->file);

                $key = tempnam(sys_get_temp_dir(), 'bxt');

                $this->createKey($key);

                $this->redefine('openssl_pkey_export', '$a, &$b', 'return false;');

                try
                {
                    $box->usePrivateKeyFile($key);
                }

                catch (Exception $exception)
                {
                }

                $this->restore('openssl_pkey_export');

                unlink($key);

                if (isset($exception))
                {
                    throw $exception;
                }
            }

            else
            {
                $this->markTestSkipped('The runkit extension is not available.');
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The details of the private key could not be retrieved:
         */
        public function testUsePrivateKeyFileDetailsError()
        {
            if (extension_loaded('runkit'))
            {
                $box = new Box($this->file);

                $key = tempnam(sys_get_temp_dir(), 'bxt');

                $this->createKey($key);

                $this->redefine('openssl_pkey_get_details', '', 'return false;');

                try
                {
                    $box->usePrivateKeyFile($key);
                }

                catch (Exception $exception)
                {
                }

                $this->restore('openssl_pkey_get_details');

                unlink($key);

                if (isset($exception))
                {
                    throw $exception;
                }
            }

            else
            {
                $this->markTestSkipped('The runkit extension is not available.');
            }
        }

        private function createKey($file, $pass = null)
        {
            $resource = openssl_pkey_new();

            openssl_pkey_export($resource, $key, $pass);

            file_put_contents($file, $key);

            $public = openssl_pkey_get_details($resource);
            $public = $public['key'];

            openssl_pkey_free($resource);

            return array($key, $public);
        }

        private function redefine($func, $args, $code)
        {
            runkit_function_rename($func, "_$func");
            runkit_function_add($func, $args, $code);
        }

        private function restore($func)
        {
            runkit_function_remove($func);
            runkit_function_rename("_$func", $func);
        }
    }