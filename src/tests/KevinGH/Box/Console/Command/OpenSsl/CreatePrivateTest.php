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
use Symfony\Component\Console\Output\OutputInterface;

class CreatePrivateTest extends CommandTestCase
{
    const COMMAND = 'openssl:create-private';

    public function testExecute()
    {
        $this->command->getHelperSet()->set(new Dialog('phpunit'));

        $this->tester->execute(array(
            'command' => self::COMMAND,
            '--out' => 'test.key',
            '--prompt' => true,
            '--type' => 'dsa',
            '--bits' => 640
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertRegExp('/PRIVATE KEY/', file_get_contents('test.key'));
    }
}

