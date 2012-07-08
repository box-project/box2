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
        KevinGH\Box\Test\Dialog,
        Symfony\Component\Console\Output\OutputInterface;

    class CreateTest extends CommandTestCase
    {
        const COMMAND = 'create';

        public function testExecute()
        {
            $this->prepareApp('phpunit');

            $file = $this->setConfig(array(
                'files' => 'src/lib/class.php',
                'git-version' => 'git_version',
                'main' => 'bin/main.php',
                'key' => 'test.pem',
                'key-pass' => true,
                'stub' => 'src/stub.php'
            ));

            $dialog = new Dialog;

            $dialog->setReturn('phpunit');

            $this->app->getHelperSet()->set($dialog);

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));

            $this->assertEquals(
                "Success!\nVersion: v1.0-ALPHA1",
                $this->command('php ' . escapeshellarg(dirname($file) . '/default.phar'))
            );
        }

        public function testExecuteDefaultStub()
        {
            $this->prepareApp('phpunit');

            $file = $this->setConfig(array(
                'files' => 'src/lib/class.php',
                'git-version' => 'git_version',
                'main' => 'bin/main.php',
                'stub' => true
            ));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));

            $this->assertEquals(
                "Success!\nVersion: v1.0-ALPHA1",
                $this->command('php ' . escapeshellarg(dirname($file) . '/default.phar'))
            );
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage Your private key password is required for signing.
         */
        public function testExecuteWithKeyAndNoPass()
        {
            file_put_contents($this->dir . '/test.pem', $this->createPrivateKey('phpunit'));

            $file = $this->setConfig(array(
                'key' => 'test.pem',
                'key-pass' => true
            ));

            $dialog = new Dialog;

            $dialog->setReturn('');

            $this->app->getHelperSet()->set($dialog);

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The main file does not exist.
         */
        public function testExecuteMainNotExist()
        {
            $file = $this->setConfig(array(
                'main' => 'bin/main.php'
            ));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The stub file does not exist.
         */
        public function testExecuteStubNotExist()
        {
            $file = $this->setConfig(array(
                'stub' => 'src/stub.php'
            ));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The stub file could not be read:
         */
        public function testExecuteStubReadError()
        {
            $this->prepareApp('phpunit');

            $file = $this->setConfig(array(
                'stub' => '/root'
            ));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        }
    }