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

use Exception;
use KevinGH\Box\Box;
use KevinGH\Box\Console\Command\Update;
use KevinGH\Box\Test\CommandTestCase;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateTest extends CommandTestCase
{
    const COMMAND = 'update';

    protected function setUp($name = 'Box', $version = '@package_version@')
    {
        parent::setUp($name, '1.0.0');
    }

    public function testExecuteIntegrityFail()
    {
        $extract = $this->property($this->command, 'extract');
        $extract('/box\\-(.+?)\\.phar/');

        $matcher = $this->property($this->command, 'match');
        $matcher('/box\\-(.+?)\\.phar/');

        $url = $this->property($this->command, 'url');
        $url($this->currentDir);

        file_put_contents($this->currentDir . '/downloads', json_encode(array(
            array(
                'name' => 'box-99.99.99.phar',
                'html_url' => $this->file(str_replace(
                    '__HALT_COMPILER',
                    '',
                    $this->getResource('example.phar', true)
                ))
            )
        )));

        try {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                '--upgrade' => true
            ));
        } catch (Exception $exception) {
        }

        $this->assertEquals('The update was corrupted.', trim($this->tester->getDisplay()));
        $this->assertInstanceOf('UnexpectedValueException', $exception);
    }
}

