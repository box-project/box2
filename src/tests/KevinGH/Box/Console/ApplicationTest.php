<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console;

    use KevinGH\Box\Test\TestCase;

    class ApplicationTest extends TestCase
    {
        public function testConstructor()
        {
            $app = new Application;

            $this->assertEquals('Box', $app->getName());
            $this->assertEquals('@git_version@', $app->getVersion());
        }

        /**
         * @expectedException ErrorException
         * @expectedExceptionMessage Test error.
         */
        public function testConstructorErrorHandler()
        {
            $app = new Application;

            trigger_error('Test error.', E_USER_ERROR);
        }

        public function testGetDefaultCommands()
        {
            $app = new Application;

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Create',
                $app->find('create')
            );

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Extract',
                $app->find('extract')
            );

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Info',
                $app->find('info')
            );

            $this->assertFalse($app->has('update'));

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Validate',
                $app->find('validate')
            );

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Verify',
                $app->find('verify')
            );
        }

        public function testGetDefaultCommandsWithOpenSSL()
        {
            $app = new Application;

            if (extension_loaded('openssl'))
            {
                $this->assertInstanceOf(
                    'KevinGH\Box\Console\Command\Key\Create',
                    $app->find('key:create')
                );

                $this->assertInstanceOf(
                    'KevinGH\Box\Console\Command\Key\Extract',
                    $app->find('key:extract')
                );
            }

            else
            {
                $this->assertFalse($app->has('key:create'));
                $this->assertFalse($app->has('key:extract'));
            }
        }

        public function testGetDefaultCommandsWithUpdate()
        {
            $app = new Application('Box', '1.0.0');

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Update',
                $app->find('update')
            );
        }

        public function testGetDefaultHelperSet()
        {
            $app = new Application;

            $this->assertFalse($app->getHelperSet()->has('amend'));

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Helper\Config',
                $app->getHelperSet()->get('config')
            );

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Helper\JSON',
                $app->getHelperSet()->get('json')
            );
        }

        public function testGetDefaultHelperSetWithOpenSSL()
        {
            $app = new Application;

            if (extension_loaded('openssl'))
            {
                $this->assertInstanceOf(
                    'KevinGH\Box\Console\Helper\OpenSSL',
                    $app->getHelperSet()->get('openssl')
                );
            }

            else
            {
                $this->assertFalse($app->getHelperSet()->has('openssl'));
            }
        }

        public function testGetDefaultHelpersWithUpdate()
        {
            $app = new Application('Box', '1.0.0');

            $this->assertInstanceOf(
                'KevinGH\Amend\Helper',
                $app->getHelperSet()->get('amend')
            );
        }
    }