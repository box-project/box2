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

    class ValidateTest extends CommandTestCase
    {
        const COMMAND = 'validate';

        public function testExecute()
        {
            $file = $this->setConfig(array(
                'files' => 'src/lib/class.php',
                'git-version' => 'git_version',
                'intercept' => true,
                'main' => 'bin/main.php',
                'metadata' => array('rand' => $rand = rand()),
                'key' => 'test.pem',
                'key-pass' => true,
                'stub' => 'src/stub.php'
            ));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ));

            $this->assertEquals(
                "The configuration file is valid.\n",
                $this->tester->getDisplay()
            );
        }

        /**
         * @expectedException Seld\JsonLint\ParsingException
         * @expectedExceptionMessage The file
         */
        public function testExecuteInvalidSyntax()
        {
            $file = $this->file('{');

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ));
        }

        public function testExecuteInvalid()
        {
            $file = $this->file('{"files": true}');

            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--config' => $file
            ));

            $this->assertRegExp(
                '/The configuration file is not valid/',
                $this->tester->getDisplay()
            );
        }
    }