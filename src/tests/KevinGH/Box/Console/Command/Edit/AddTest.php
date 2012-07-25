<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console\Command\Edit;

    use Exception,
        KevinGH\Box\Test\CommandTestCase,
        KevinGH\Box\Test\Dialog,
        Phar,
        Symfony\Component\Console\Output\OutputInterface,
        Symfony\Component\Process\Process;

    class AddTest extends CommandTestCase
    {
        const COMMAND = 'edit:add';

        private $phar;

        protected function setUp($name = 'Box', $version = '@git_version@')
        {
            parent::setUp();

            copy($this->resource('test.phar', true), $this->phar = $this->dir() . '/test.phar');
        }

        public function testExecute()
        {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $this->resource('app/alt.json', true),
                'phar' => $this->phar,
                'file' => $this->resource('app/bin/alt.php', true),
                'relative' => 'main.php'
            ));

            $process = new Process('php ' . escapeshellarg($this->phar));

            $process->run();

            $this->assertEquals("Hello, edited world!\n", $process->getOutput());
        }

        public function testExecuteBin()
        {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--bin' => true,
                '--config' => $this->resource('app/alt.json', true),
                'phar' => $this->phar,
                'file' => $this->resource('app/bin/alt.php', true),
                'relative' => 'main.php'
            ));

            $process = new Process('php ' . escapeshellarg($this->phar));

            $process->run();

            $this->assertEquals("Hello, @name@!\n", $process->getOutput());
        }

        public function testExecuteStub()
        {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $this->resource('app/alt.json', true),
                '--stub' => true,
                'phar' => $this->phar,
                'file' => $this->resource('app/src/alt.php', true)
            ));

            $process = new Process('php ' . escapeshellarg($this->phar));

            $process->run();

            $this->assertEquals("Hello, stubbed world!\n", $process->getOutput());
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage PHAR writing has been disabled by "phar.readonly".
         */
        public function testExecuteReadonly()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            $this->redefine('ini_get', '', 'return "1";');

            try
            {
                $this->tester->execute(array(
                    'command' => self::COMMAND,
                    '--config' => $this->resource('app/alt.json', true),
                    'phar' => 'asdf',
                    'file' => 'asdf'
                ));
            }

            catch (Exception $exception)
            {
            }

            $this->restore('ini_get');

            if (isset($exception))
            {
                throw $exception;
            }
        }

        public function testExecuteTooManyOptions()
        {
            $this->assertEquals(1, $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $this->resource('app/alt.json', true),
                '--bin' => true,
                '--stub' => true,
                'phar' => 'asdf',
                'file' => 'asdf'
            )));

            $this->assertRegExp(
                '/You can only use one of the options/',
                $this->tester->getDisplay()
            );
        }

        public function testExecutePHARNotExist()
        {
            $this->assertEquals(1, $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $this->resource('app/alt.json', true),
                'phar' => 'asdf',
                'file' => 'asdf'
            )));

            $this->assertRegExp(
                '/The PHAR file "asdf" does not exist/',
                $this->tester->getDisplay()
            );
        }

        public function testExecuteFileNotExist()
        {
            $this->assertEquals(1, $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $this->resource('app/alt.json', true),
                'phar' => $this->resource('test.phar', true),
                'file' => 'asdf'
            )));

            $this->assertRegExp(
                '/The file "asdf" does not exist/',
                $this->tester->getDisplay()
            );
        }

        public function testExecuteRelativeRequired()
        {
            $this->assertEquals(1, $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $this->resource('app/alt.json', true),
                'phar' => $this->resource('test.phar', true),
                'file' => $this->resource('app/bin/alt.php', true)
            )));

            $this->assertRegExp(
                '/The relative path is required for non\-stub files/',
                $this->tester->getDisplay()
            );
        }

        /**
         * @expectedException UnexpectedValueException
         */
        public function testExecuteCorruptPHAR()
        {
            file_put_contents($this->phar, str_replace(
                '__HALT_COMPILER',
                '',
                file_get_contents($this->phar)
            ));

            try
            {
                $this->tester->execute(array(
                    'command' => self::COMMAND,
                    '--config' => $this->resource('app/alt.json', true),
                    'phar' => $this->phar,
                    'file' => $this->resource('app/bin/alt.php', true),
                    'relative' => 'bin/main.php'
                ));
            }

            catch (Exception $exception)
            {
            }

            $this->assertRegExp(
                '/The PHAR "(.+?)" could not be opened./',
                $this->tester->getDisplay()
            );

            if (isset($exception)) throw $exception;
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The stub file "/root" could not be read:
         */
        public function testExecuteStubReadFail()
        {
            $this->assertEquals(1, $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $this->resource('app/alt.json', true),
                '--stub' => true,
                'phar' => $this->phar,
                'file' => '/root'
            )));
        }
    }