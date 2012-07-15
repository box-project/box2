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
        KevinGH\Box\Box,
        KevinGH\Box\Test\TestCase;

    class BoxTest extends TestCase
    {
        public function testConstructorDefaults()
        {
            $box = new Box('test.phar');

            $name = $this->property($box, 'name');
            $alias = $this->property($box, 'alias');

            $this->assertEquals('test.phar', $name());
            $this->assertEquals('default.phar', $alias());
        }

        public function testConstructorArgs()
        {
            $box = new Box('test.phar', 0, 'test.phar');

            $name = $this->property($box, 'name');
            $alias = $this->property($box, 'alias');

            $this->assertEquals('test.phar', $name());
            $this->assertEquals('test.phar', $alias());
        }

        public function testCompactSourceDefault()
        {
            $box = new Box('test.phar');

            $this->assertEquals(
                $this->resource('class-compacted.php'),
                $box->compactSource($this->resource('class.php'))
            );
        }

        public function testCompactSourceCustomized()
        {
            $box = new Box('test.phar');

            $box->setCompactor(function($source)
            {
                return str_replace('Success', 'Modified', $source);
            });

            $this->assertEquals(
                $this->resource('class-custom.php'),
                $box->compactSource($this->resource('class.php'))
            );
        }

        public function testCreateStubNoMain()
        {
            $box = new Box('test.phar');

            $this->assertEquals(
                $this->resource('stub.php'),
                $box->createStub()
            );
        }

        public function testCreateStubWithMain()
        {
            $box = new Box('test.phar');

            $property = $this->property($box, 'main');

            $property('bin/main.php');

            $this->assertEquals(
                $this->resource('stub-main.php'),
                $box->createStub()
            );
        }

        public function testCreateStubWithIntercept()
        {
            $box = new Box('test.phar');

            $box->setIntercept(true);

            $this->assertEquals(
                $this->resource('stub-intercept.php'),
                $box->createStub()
            );
        }

        public function testDoReplacements()
        {
            $box = new Box('test.phar');

            $box->setReplacements(array('placeholder' => 'replaced value'));

            $this->assertEquals(
                $this->resource('replace-after.php'),
                $box->doReplacements($this->resource('replace-before.php'))
            );
        }

        public function testImportFile()
        {
            $box = new Box($this->dir() . '/test.phar');

            $box->importFile('test/file.php', RESOURCES . 'file-imported.php');

            $box->extractTo($dir = $this->dir());

            $this->assertFileEquals(RESOURCES . 'file-exported.php', "$dir/test/file.php");
        }

        public function testImportFileMain()
        {
            $box = new Box($this->dir() . '/test.phar');

            $box->importFile('bin/main.php', RESOURCES . 'main-imported.php', true);

            $property = $this->property($box, 'main');

            $box->extractTo($dir = $this->dir());

            $this->assertEquals('bin/main.php', $property());
            $this->assertFileEquals(RESOURCES . 'main-exported.php', "$dir/bin/main.php");
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The file does not exist: /does/not/exist
         */
        public function testImportFileNotExist()
        {
            $box = new Box('test.phar');

            $box->importFile('not/exist', '/does/not/exist');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The file could not be read:
         */
        public function testImportFileReadError()
        {
            $box = new Box('test.phar');

            $box->importFile('root', '/root');
        }

        public function testImportSource()
        {
            $box = new Box($this->dir() . '/test.phar');

            $box->importSource('test/file.php', $this->resource('file-imported.php'));

            $box->extractTo($dir = $this->dir());

            $this->assertFileEquals(RESOURCES . 'file-exported.php', "$dir/test/file.php");
        }

        public function testImportSourceMain()
        {
            $box = new Box($this->dir() . '/test.phar');

            $box->importSource('bin/main.php', $this->resource('main-imported.php'), true);

            $property = $this->property($box, 'main');

            $box->extractTo($dir = $this->dir());

            $this->assertEquals('bin/main.php', $property());
            $this->assertFileEquals(RESOURCES . 'main-exported.php', "$dir/bin/main.php");
        }

        public function testSetCompactor()
        {
            $box = new Box('test.phar');

            $box->setCompactor(function($source)
            {
                return str_replace('class', 'klass', $source);
            });

            $this->assertEquals('klass Test {}', $box->compactSource('class Test {}'));
        }

        public function testSetReplacements()
        {
            $box = new Box('test.phar');

            $box->setReplacements(array(
                'key1' => 123,
                'key2' => 'abc'
            ));

            $this->assertEquals('abc123', $box->doReplacements('@key2@@key1@'));
        }

        public function testUsePrivateKeyFile()
        {
            if (false === extension_loaded('openssl'))
            {
                $this->markTestSkipped('The "openssl" extension is not available.');

                return;
            }

            $key = $this->file($this->createPrivateKey('phpunit'));

            $file = $this->getApp(true, 'phpunit');

            $box = new Box($file);

            $signature = $box->getSignature();

            $this->assertEquals('OpenSSL', $signature['hash_type']);
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The private key file does not exist.
         */
        public function testUsePrivateKeyFileInvalidFile()
        {
            $box = new Box('test.phar');

            $box->usePrivateKeyFile('/does/not/exist');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The "openssl" extension is not available.
         */
        public function testUsePrivateKeyFileNoExtension()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $this->redefine('extension_loaded', '', 'return false;');

            $box = new Box('test.phar');

            try
            {
                $box->usePrivateKeyFile($this->file());
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

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The private key file could not be read:
         */
        public function testUsePrivateKeyFileReadError()
        {
            $box = new Box('test.phar');

            $box->usePrivateKeyFile('/root');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The public key could not be written:
         */
        public function testUsePrivateKeyFileWriteError()
        {
            $key = $this->file($this->createPrivateKey('phpunit'));

            $box = new Box('/root/test.phar');

            $box->usePrivateKeyFile($key, 'phpunit');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The private key could not be parsed:
         */
        public function testGetKeyParseError()
        {
            if (false === extension_loaded('openssl'))
            {
                $this->markTestSkipped('The "openssl" extension is not available.');

                return;
            }

            $box = new Box('test.phar');

            $method = $this->method($box, 'getKeys');

            $method('test');
        }

        /**
         * @expectedException RuntimeException
         * @expectedException The private key could not be exported:
         */
        public function testGetKeyExportError()
        {
            if (false === extension_loaded('openssl'))
            {
                $this->markTestSkipped('The "openssl" extension is not available.');

                return;
            }

            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $key = $this->createPrivateKey('phpunit');

            $box = new Box('test.phar');

            $this->redefine('openssl_pkey_export', '$a, &$b', 'return false;');

            $method = $this->method($box, 'getKeys');

            try
            {
                $method($key, 'phpunit');
            }

            catch (Exception $exception)
            {
            }

            $this->restore('openssl_pkey_export');

            if (isset($exception))
            {
                throw $exception;
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedException The details of the private key could not be retrieved:
         */
        public function testGetKeyDetailsError()
        {
            if (false === extension_loaded('openssl'))
            {
                $this->markTestSkipped('The "openssl" extension is not available.');

                return;
            }

            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $key = $this->createPrivateKey('phpunit');

            $box = new Box('test.phar');

            $this->redefine('openssl_pkey_get_details', '', 'return false;');

            $method = $this->method($box, 'getKeys');

            try
            {
                $method($key, 'phpunit');
            }

            catch (Exception $exception)
            {
            }

            $this->restore('openssl_pkey_get_details');

            if (isset($exception))
            {
                throw $exception;
            }
        }
    }