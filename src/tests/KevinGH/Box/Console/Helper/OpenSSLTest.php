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

    use Exception,
        KevinGH\Box\Test\TestCase;

    if (extension_loaded('openssl'))
    {
        class OpenSSLTest extends TestCase
        {
            private $helper;

            protected function setUp()
            {
                $this->helper = new OpenSSL;
            }

            public function testCreatePrivate()
            {
                $this->assertRegExp(
                    '/PRIVATE KEY/',
                    $this->helper->createPrivate('test', 'dsa', 512)
                );
            }

            /**
             * @expectedException InvalidArgumentException
             * @expectedExceptionMessage Invalid key type:
             */
            public function testCreatePrivateInvalidType()
            {
                $this->helper->createPrivate('test', 'test');
            }

            /**
             * @expectedException RuntimeException
             * @expectedExceptionMessage The private key could not be created:
             */
            public function testCreatePrivateNewFail()
            {
                if (false === extension_loaded('runkit'))
                {
                    $this->markTestSkipped('The "runkit" extension is not available.');

                    return;
                }

                $this->redefine('openssl_pkey_new', '', 'return false;');

                try
                {
                    $this->helper->createPrivate('test');
                }

                catch (Exception $e)
                {
                }

                $this->restore('openssl_pkey_new');

                if (isset($e)) throw $e;
            }

            /**
             * @expectedException RuntimeException
             * @expectedExceptionMessage The details of the private key could not be retrieved:
             */
            public function testCreatePrivateExportFail()
            {
                if (false === extension_loaded('runkit'))
                {
                    $this->markTestSkipped('The "runkit" extension is not available.');

                    return;
                }

                $this->redefine('openssl_pkey_export', '$a, &$b = null, $c', 'return false;');

                try
                {
                    $this->helper->createPrivate('test');
                }

                catch (Exception $e)
                {
                }

                $this->restore('openssl_pkey_export');

                if (isset($e)) throw $e;
            }

            public function testCreatePrivateFile()
            {
                $this->helper->createPrivateFile(
                    $file = $this->file(),
                    'test',
                    'dsa',
                    512
                );

                $this->assertRegExp(
                    '/PRIVATE KEY/',
                    file_get_contents($file)
                );
            }

            /**
             * @expectedException RuntimeException
             * @expectedExceptionMessage The private key file
             */
            public function testCreatePrivateFileWriteError()
            {
                $this->helper->createPrivateFile('/does/not/exist');
            }

            public function testCreatePublic()
            {
                $private = $this->helper->createPrivate('test');

                $this->assertRegExp(
                    '/PUBLIC KEY/',
                    $this->helper->createPublic($private, 'test')
                );
            }

            public function testGetKeyTypes()
            {
                $types = $this->property($this->helper, 'algorithms');

                $this->assertEquals($types(), $this->helper->getKeyTypes());
            }

            /**
             * @expectedException RuntimeException
             * @expectedExceptionMessage The private key could not be processed:
             */
            public function testCreatePublicPrivateFail()
            {
                $this->helper->createPublic('test');
            }

            /**
             * @expectedException RuntimeException
             * @expectedExceptionMessage The details of the private key could not be extracted:
             */
            public function testCreatePublicDetailsFail()
            {
                if (false === extension_loaded('runkit'))
                {
                    $this->markTestSkipped('The "runkit" extension is not available.');

                    return;
                }

                $this->redefine('openssl_pkey_get_details', '', 'return false;');

                try
                {
                    $this->helper->createPublic($this->helper->createPrivate());
                }

                catch (Exception $e)
                {
                }

                $this->restore('openssl_pkey_get_details');

                if (isset($e)) throw $e;
            }

            public function testCreatePublicFile()
            {
                $this->helper->createPublicFile(
                    $file = $this->file(),
                    $this->helper->createPrivate('test'),
                    'test'
                );

                $this->assertRegExp('/PUBLIC KEY/', file_get_contents($file));
            }

            /**
             * @expectedException RuntimeException
             * @expectedExceptionMessage The public key file
             */
            public function testCreatePublicFileWriteFail()
            {
                if (false === extension_loaded('runkit'))
                {
                    $this->markTestSkipped('The "runkit" extension is not available.');

                    return;
                }

                $this->redefine('file_put_contents', '', 'return false;');

                try
                {
                    $this->helper->createPublicFile(
                        $this->file(),
                        $this->helper->createPrivate()
                    );
                }

                catch (Exception $e)
                {
                }

                $this->restore('file_put_contents');

                if (isset($e)) throw $e;
            }

            public function testCreatePublicFileFromFile()
            {
                $this->helper->createPrivateFile($file = $this->file(), 'test');

                $this->helper->createPublicFileFromFile(
                    $file,
                    $pub = $this->file(),
                    'test'
                );

                $this->assertRegExp('/PUBLIC KEY/', file_get_contents($pub));
            }
        }
    }