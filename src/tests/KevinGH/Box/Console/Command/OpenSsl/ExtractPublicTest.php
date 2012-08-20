<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Command\OpenSsl;

use KevinGH\Box\Box;
use KevinGH\Box\Test\CommandTestCase;
use KevinGH\Box\Test\Dialog;
use KevinGH\Elf\OpenSsl;
use Symfony\Component\Console\Output\OutputInterface;

class ExtractPublicTest extends CommandTestCase
{
    const COMMAND = 'openssl:extract-public';

    public function testExecute()
    {
        $this->command->getHelperSet()->set(new Dialog('phpunit'));

        $openssl = new OpenSsl();

        $openssl->createPrivateKeyFile('private.key', 'phpunit');

        $this->tester->execute(array(
            'command' => self::COMMAND,
            'private' => 'private.key',
            '--out' => 'test.key',
            '--prompt' => true
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertRegExp('/PUBLIC KEY/', file_get_contents('test.key'));
    }
}

