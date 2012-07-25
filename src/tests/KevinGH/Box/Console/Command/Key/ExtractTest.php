<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console\Command\Key;

    use KevinGH\Box\Console\Application,
        KevinGH\Box\Test\CommandTestCase,
        KevinGH\Box\Test\Dialog,
        Symfony\Component\Console\Tester\CommandTester;

    if (extension_loaded('openssl'))
    {
        class ExtractTest extends CommandTestCase
        {
            const COMMAND = 'key:extract';

            public function testExecute()
            {
                $this->app
                     ->getHelperSet()
                     ->get('openssl')
                     ->createPrivateFile($in = $this->file(), 'test');

                $out = $this->file();

                $dialog = new Dialog;

                $dialog->setReturn('test');

                $this->app->getHelperSet()->set($dialog);

                $this->tester->execute(array(
                    'command' => self::COMMAND,
                    '--in' => $in,
                    '--out' => $out
                ));

                $this->assertRegExp('/PUBLIC KEY/', file_get_contents($out));
            }

            public function testExecutePrivateNotExist()
            {
                $this->tester->execute(array(
                    'command' => self::COMMAND,
                    '--in' => '/does/not/exist'
                ));

                $this->assertRegExp(
                    '/does not exist/',
                    $this->tester->getDisplay()
                );
            }
        }
    }