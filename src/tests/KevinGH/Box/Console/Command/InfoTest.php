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

use KevinGH\Box\Box;
use KevinGH\Box\Test\CommandTestCase;
use Symfony\Component\Console\Output\OutputInterface;

class InfoTest extends CommandTestCase
{
    const COMMAND = 'info';

    public function testExecuteNoFile()
    {
        $this->tester->execute(array('command' => self::COMMAND));

        $compression = join(', ', Box::getSupportedCompression());
        $signature = join(', ', Box::getSupportedSignatures());
        $version = Box::apiVersion();

        $this->assertEquals(
            <<<OUTPUT
<prefix>PHAR</prefix> v$version
<prefix>INFO</prefix> Compression Algorithms: $compression
<prefix>INFO</prefix> Signature Algorithms: $signature

OUTPUT
            ,
            $this->tester->getDisplay()
        );
    }

    public function testExecuteWithFile()
    {
        $this->tester->execute(array(
            'command' => self::COMMAND,
            'phar' => $file = $this->getResource('example.phar')
        ));

        $this->assertEquals(
            <<<OUTPUT
<prefix>FILE</prefix> $file
<prefix>INFO</prefix> API: v1.1.0
<prefix>INFO</prefix> Compression: None
<prefix>INFO</prefix> Signature: SHA-1

OUTPUT
            ,
            $this->tester->getDisplay()
        );
    }
}

