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

    use PHPUnit_Framework_TestCase;

    class ApplicationTest extends PHPUnit_Framework_TestCase
    {
        private $app;

        protected function setUp()
        {
            $this->app = new Application;
        }

        public function testConstructor()
        {
            $this->assertEquals('Box', $this->app->getName());
            $this->assertEquals('@git_version@', $this->app->getVersion());
        }

        /**
         * @expectedException ErrorException
         * @expectedExceptionMessage Test error.
         */
        public function testErrorHandler()
        {
            trigger_error('Test error.', E_USER_ERROR);
        }

        public function testGetDefaultCommands()
        {
            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Create',
                $this->app->find('create')
            );

            $this->assertInstanceOf(
                'KevinGH\Box\Console\Command\Verify',
                $this->app->find('verify')
            );
        }

        public function testGetDefaultHelperSet()
        {
            $this->assertInstanceOf(
                'KevinGH\Box\Console\Helper\Config',
                $this->app->getHelperSet()->get('config')
            );
        }
    }