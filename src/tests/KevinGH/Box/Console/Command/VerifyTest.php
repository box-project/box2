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

    class VerifyTest extends CommandTestCase
    {
        const COMMAND = 'verify';

        public function testExecuteSigned()
        {
            $file = $this->getApp(true, 'phpunit');

            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => $file
            ));

            $this->assertEquals('The PHAR is verified and signed.', trim($this->tester->getDisplay()));
        }

        public function testExecuteUnsigned()
        {
            $file = $this->getApp();

            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => $file
            ));

            $this->assertEquals('The PHAR is verified but not signed.', trim($this->tester->getDisplay()));
        }

        public function testExecuteFailsVerify()
        {
            $file = $this->getApp();

            file_put_contents($file, $copy = str_replace('class', 'klass', file_get_contents($file)));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => $file
            ));

            $this->assertEquals('The PHAR failed verification.', trim($this->tester->getDisplay()));
        }

        /**
         * @expectedException UnexpectedValueException
         */
        public function testExecuteFailsVerifyVerbose()
        {
            $file = $this->getApp();

            file_put_contents($file, $copy = str_replace('class', 'klass', file_get_contents($file)));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The PHAR does not exist.
         */
        public function testExecuteNotExist()
        {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => '/does/not/exist'
            ));
        }
    }