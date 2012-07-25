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

    class RemoveTest extends CommandTestCase
    {
        const COMMAND = 'edit:remove';

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
                'phar' => $this->phar,
                'relative' => array('main.php')
            ));

            $process = new Process('php ' . escapeshellarg($this->phar));

            $process->run();

            $this->assertRegExp('/manifest/', $process->getOutput());
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
                    'phar' => 'asdf',
                    'relative' => 'asdf'
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

        public function testExecutePHARNotExist()
        {
            $this->assertEquals(1, $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => 'asdf',
                'relative' => 'asdf'
            )));

            $this->assertRegExp(
                '/The PHAR file "asdf" does not exist/',
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
                    'phar' => $this->phar,
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
    }