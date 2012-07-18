<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console\Command;

    use Exception,
        KevinGH\Box\Test\CommandTestCase,
        KevinGH\Box\Test\Dialog,
        Phar,
        Symfony\Component\Console\Output\OutputInterface;

    class UpdateTest extends CommandTestCase
    {
        const COMMAND = 'update';

        private $self;

        protected function setUp()
        {
            parent::setUp();

            $name = $this->property($this->command, 'updateName');

            $name('box.phar');

            $this->self = $_SERVER['argv'][0];

            $_SERVER['argv'][0] = $this->file();
        }

        protected function tearDown()
        {
            $_SERVER['argv'][0] = $this->self;
        }

        public function testExecute()
        {
            $this->app->setVersion('abcdef0');

            $this->setURL($this->file(utf8_encode(json_encode(array(
                array(
                    'created_at' => '2012-07-17T21:49:11Z',
                    'description' => 'abcdef0123456789abcdef0123456789abcdef01',
                    'html_url' => $this->resource('test.phar', true),
                    'name' => 'box.phar'
                )
            )))));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--force' => true
            ));

            $this->assertEquals("Box has been updated!\n", $this->tester->getDisplay());
        }

        public function testExecuteAlreadyUpdated()
        {
            $this->app->setVersion('abcdef0');

            $this->setURL($this->file(utf8_encode(json_encode(array(
                array(
                    'created_at' => '2012-07-17T21:49:11Z',
                    'description' => 'abcdef0123456789abcdef0123456789abcdef01',
                    'html_url' => $this->resource('test.phar', true),
                    'name' => 'box.phar'
                )
            )))));

            $this->tester->execute(array('command' => self::COMMAND));

            $this->assertEquals("Box is up-to-date.\n", $this->tester->getDisplay());
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The update information could not be retrieved:
         */
        public function testGetInfoBadURL()
        {
            $this->setURL('/does/not/exist');

            $method = $this->method($this->command, 'getInfo');

            $method();
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage Unable to find any updates.
         */
        public function testGetInfoNoUpdateFound()
        {
            $this->setURL($this->file(utf8_encode(json_encode(array(
                array(
                    'created_at' => '2012-07-17T21:49:11Z',
                    'description' => 'abcdef0123456789abcdef0123456789abcdef01',
                    'html_url' => $this->resource('test.phar', true),
                    'name' => 'test.phar'
                )
            )))));

            $method = $this->method($this->command, 'getInfo');

            $method();
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The update file could not be opened:
         */
        public function testGetUpdateOpenFail()
        {
            $this->setURL($this->file(utf8_encode(json_encode(array(
                array(
                    'created_at' => '2012-07-17T21:49:11Z',
                    'description' => 'abcdef0123456789abcdef0123456789abcdef01',
                    'html_url' => '/does/not/exist',
                    'name' => 'box.phar'
                )
            )))));

            $method = $this->method($this->command, 'getUpdate');

            $method();
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The temporary file could not be opened:
         */
        public function testGetUpdateTempOpenFail()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $this->setURL($this->file(utf8_encode(json_encode(array(
                array(
                    'created_at' => '2012-07-17T21:49:11Z',
                    'description' => 'abcdef0123456789abcdef0123456789abcdef01',
                    'html_url' => $this->resource('test.phar', true),
                    'name' => 'box.phar'
                )
            )))));

            $method = $this->method($this->command, 'getUpdate');

            $this->redefine('fopen', '$a, $b', 'if (0 === strpos($a, sys_get_temp_dir())) return false; return _fopen($a, $b);');

            try
            {
                $method();
            }

            catch (Exception $exception)
            {
            }

            $this->restore('fopen');

            if (isset($exception))
            {
                throw $exception;
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The update file could not be read:
         */
        public function testGetUpdateReadFail()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $this->setURL($this->file(utf8_encode(json_encode(array(
                array(
                    'created_at' => '2012-07-17T21:49:11Z',
                    'description' => 'abcdef0123456789abcdef0123456789abcdef01',
                    'html_url' => $this->resource('test.phar', true),
                    'name' => 'box.phar'
                )
            )))));

            $method = $this->method($this->command, 'getUpdate');

            $this->redefine('fread', '', 'return false;');

            try
            {
                $method();
            }

            catch (Exception $exception)
            {
            }

            $this->restore('fread');

            if (isset($exception))
            {
                throw $exception;
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The temporary file could not be written:
         */
        public function testGetUpdateTempWriteFail()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $this->setURL($this->file(utf8_encode(json_encode(array(
                array(
                    'created_at' => '2012-07-17T21:49:11Z',
                    'description' => 'abcdef0123456789abcdef0123456789abcdef01',
                    'html_url' => $this->resource('test.phar', true),
                    'name' => 'box.phar'
                )
            )))));

            $method = $this->method($this->command, 'getUpdate');

            $this->redefine('fwrite', '', 'return false;');

            try
            {
                $method();
            }

            catch (Exception $exception)
            {
            }

            $this->restore('fwrite');

            if (isset($exception))
            {
                throw $exception;
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The PHAR is corrupt:
         */
        public function testGetUpdateCorrupted()
        {
            $corrupt = $this->file(123);

            $this->setURL($this->file(utf8_encode(json_encode(array(
                array(
                    'created_at' => '2012-07-17T21:49:11Z',
                    'description' => 'abcdef0123456789abcdef0123456789abcdef01',
                    'html_url' => $corrupt,
                    'name' => 'box.phar'
                )
            )))));

            $method = $this->method($this->command, 'getUpdate');

            $method();
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage Use `git pull` to update.
         */
        public function testIsCurrentRepo()
        {
            $method = $this->method($this->command, 'isCurrent');

            $method();
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The application could not be replaced:
         */
        public function testReplaceSelfRenameError()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $method = $this->method($this->command, 'replaceSelf');

            $this->redefine('rename', '', 'return false;');

            try
            {
                $method('/test/self');
            }

            catch (Exception $exception)
            {
            }

            $this->restore('rename');

            if (isset($exception))
            {
                throw $exception;
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The application could not be marked as executable:
         */
        public function testReplaceSelfChmodError()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $method = $this->method($this->command, 'replaceSelf');

            $this->redefine('chmod', '', 'return false;');

            $temp = $_SERVER['argv'][0];

            $_SERVER['argv'][0] = $this->file();

            try
            {
                $method($this->file());
            }

            catch (Exception $exception)
            {
            }

            $_SERVER['argv'][0] = $temp;

            $this->restore('chmod');

            if (isset($exception))
            {
                throw $exception;
            }
        }

        private function setURL($url)
        {
            $property = $this->property($this->command, 'updateURL');

            $property($url);
        }
    }