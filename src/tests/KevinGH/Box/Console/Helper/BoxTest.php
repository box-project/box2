<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console;

use KevinGH\Box\Test\HelperTestCase;
use KevinGH\Elf;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class BoxTest extends HelperTestCase
{
    protected $helperClass = 'KevinGH\\Box\\Console\\Helper\\Box';

    protected function createHelperSet()
    {
        return new HelperSet(array(
            new Elf\Json()
        ));
    }

    public function testChmodPhar()
    {
        touch('test.phar');
        touch('test.phar.bz2');
        touch('test.phar.gz');
        touch('test.phar.tar');
        touch('test.phar.zip');
        touch('test.phar.zzz');

        $this->helper->chmodPhar('test.phar', '0755');

        $this->assertEquals(0755, fileperms('test.phar') & 511);
        $this->assertEquals(0755, fileperms('test.phar.bz2') & 511);
        $this->assertEquals(0755, fileperms('test.phar.gz') & 511);
        $this->assertEquals(0755, fileperms('test.phar.tar') & 511);
        $this->assertEquals(0755, fileperms('test.phar.zip') & 511);
        $this->assertNotEquals(0755, fileperms('test.phar.zzz') & 511);
    }

    public function testFind()
    {
        file_put_contents('box.dist.json', '{}');

        $a = $this->helper->find(null);

        file_put_contents('box.json', '{}');

        $b = $this->helper->find(null);

        file_put_contents('test.json', '{}');

        $c = $this->helper->find('test.json');

        $this->assertInstanceOf('KevinGH\\Box\\Console\\Configuration', $a);
        $this->assertInstanceOf('KevinGH\\Box\\Console\\Configuration', $b);
        $this->assertInstanceOf('KevinGH\\Box\\Console\\Configuration', $c);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The configuration could not be found.
     */
    public function testFindNotFound()
    {
        $this->helper->find(null);
    }

    public function testGetCurrentDir()
    {
        if ($this->redeclare($this, 'getcwd', '', 'return "test";')) {
            return;
        }

        $this->assertEquals('test', $this->helper->getCurrentDir());
    }

    public function testGetCurrentDirUsingCd()
    {
        if ($this->redeclare($this, 'getcwd', '', 'return false;')) {
            return;
        }

        $_SERVER['CD'] = 'test';

        unset($_SERVER['PWD']);

        $this->assertEquals('test', $this->helper->getCurrentDir());
    }

    public function testGetCurrentDirUsingPwd()
    {
        if ($this->redeclare($this, 'getcwd', '', 'return false;')) {
            return;
        }

        $_SERVER['PWD'] = 'test';

        unset($_SERVER['CD']);

        $this->assertEquals('test', $this->helper->getCurrentDir());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The current working directory path could not be found.
     */
    public function testGetCurrentDirNotFound()
    {
        if ($this->redeclare($this, 'getcwd', '', 'return false;')) {
            return;
        }

        unset($_SERVER['CD'], $_SERVER['PWD']);

        $this->helper->getCurrentDir();
    }

    /**
     * @dataProvider getVerbosityLevels
     */
    public function testIsVerbose($level, $expected)
    {
        $this->assertSame(
            $expected,
            $this->helper->isVerbose(new StreamOutput($this->createStream(), $level, false))
        );
    }

    public function testPutLn()
    {
        $notVerbose = new StreamOutput(
            $this->createStream(),
            OutputInterface::VERBOSITY_NORMAL,
            false
        );

        $verbose = new StreamOutput(
            $this->createStream(),
            OutputInterface::VERBOSITY_VERBOSE,
            false
        );

        $this->helper->setOutput($notVerbose);
        $this->helper->putln('TEST', 'A non-verbose message.');
        $this->helper->putln('TEST', 'A verbose message.', true);

        $this->helper->setOutput($verbose);
        $this->helper->putln('TEST', 'A non-verbose message.');
        $this->helper->putln('TEST', 'A verbose message.', true);

        $this->assertEquals(
            "<prefix>TEST</prefix> A non-verbose message.\n",
            $this->getStreamContents($notVerbose->getStream())
        );

        $this->assertEquals(
            "<prefix>TEST</prefix> A non-verbose message.\n" .
            "<prefix>TEST</prefix> A verbose message.\n",
            $this->getStreamContents($verbose->getStream())
        );
    }

    public function testRemovePhar()
    {
        touch('test.phar');
        touch('test.phar.bz2');
        touch('test.phar.gz');
        touch('test.phar.tar');
        touch('test.phar.zip');

        $this->helper->removePhar('test.phar');

        $this->assertFileNotExists('test.phar');
        $this->assertFileNotExists('test.phar.bz2');
        $this->assertFileNotExists('test.phar.gz');
        $this->assertFileNotExists('test.phar.tar');
        $this->assertFileNotExists('test.phar.zip');
    }

    public function testSetOutput()
    {
        $output = $this->property($this->helper, 'output');
        $stream = new StreamOutput(
            $this->createStream(),
            OutputInterface::VERBOSITY_NORMAL,
            false
        );

        $this->assertNull($output());

        $this->helper->setOutput($stream);

        $this->assertSame($stream, $output());
    }

    public function getVerbosityLevels()
    {
        return array(
            array(OutputInterface::VERBOSITY_NORMAL, false),
            array(OutputInterface::VERBOSITY_QUIET, false),
            array(OutputInterface::VERBOSITY_VERBOSE, true)
        );
    }
}

