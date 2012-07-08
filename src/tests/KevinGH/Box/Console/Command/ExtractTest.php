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

    use KevinGH\Box\Test\CommandTestCase,
        Symfony\Component\Console\Output\OutputInterface;

    class ExtractTest extends CommandTestCase
    {
        const COMMAND = 'extract';

        public function testExecute()
        {
            $file = $this->getApp();

            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => $file
            ));

            $dir = "$file-contents";

            $this->assertFileExists("$dir/bin/main.php");
            $this->assertFileExists("$dir/src/lib/class.php");
        }

        public function testExecuteWithOutputPath()
        {
            $file = $this->getApp();

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--out' => $this->dir,
                'phar' => $file
            ));

            $this->assertFileExists("{$this->dir}/bin/main.php");
            $this->assertFileExists("{$this->dir}/src/lib/class.php");
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The PHAR does not exist.
         */
        public function testExecutePharNotExist()
        {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => '/does/not/exist'
            ));
        }

        public function testExecutePharException()
        {
            $file = $this->getApp();

            $exit = $this->tester->execute(array(
                'command' => self::COMMAND,
                '--out' => '/root',
                'phar' => $file
            ));

            $this->assertEquals(1, $exit);
        }

        /**
         * @expectedException PharException
         */
        public function testExecutePharExceptionVerbose()
        {
            $file = $this->getApp();

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--out' => '/root',
                'phar' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        }

        public function testExecutePharReadError()
        {
            $file = $this->file('invalid phar');

            $exit = $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => $file
            ));

            $this->assertEquals(1, $exit);
            $this->assertEquals('The PHAR could not be opened.', trim($this->tester->getDisplay()));
        }

        /**
         * @expectedException UnexpectedValueException
         */
        public function testExecutePharReadErrorVerbose()
        {
            $file = $this->file('invalid phar');

            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        }
    }