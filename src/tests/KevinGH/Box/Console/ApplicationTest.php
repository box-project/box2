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
                'KevinGH\Box\Console\Command\Update',
                $app->find('update')
            );

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Verify',
                $app->find('verify')
            );
        }

        public function testGetDefaultHelperSet()
        {
            $app = new Application;

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Helper\Config',
                $app->getHelperSet()->get('config')
            );
        }
    }