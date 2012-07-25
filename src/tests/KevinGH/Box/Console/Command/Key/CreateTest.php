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
        class CreateTest extends CommandTestCase
        {
            const COMMAND = 'key:create';

            public function testExecute()
            {
                $dialog = new Dialog;

                $dialog->setReturn('test');

                $this->app->getHelperSet()->set($dialog);

                $this->tester->execute(array(
                    'command' => self::COMMAND,
                    '--bits' => 512,
                    '--out' => $file = $this->file(),
                    '--type' => 'dsa'
                ));

                $this->assertFileExists($file);
                $this->assertRegExp('/PRIVATE KEY/', $key = file_get_contents($file));

                $r = openssl_pkey_get_private($key, 'test');
                $d = openssl_pkey_get_details($r);
                     openssl_free_key($r);

                $this->assertEquals(OPENSSL_KEYTYPE_DSA, $d['type']);
                $this->assertEquals(512, $d['bits']);
            }

            public function testExecuteInvalidType()
            {
                $this->tester->execute(array(
                    'command' => self::COMMAND,
                    '--bits' => 512,
                    '--out' => $file = $this->file(),
                    '--type' => 'test'
                ));

                $this->assertRegExp(
                    '/Key type not supported/',
                    $this->tester->getDisplay()
                );
            }
        }
    }