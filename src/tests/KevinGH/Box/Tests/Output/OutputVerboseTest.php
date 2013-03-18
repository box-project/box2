<?php

namespace KevinGH\Box\Tests\Output;

use Herrera\PHPUnit\TestCase;
use KevinGH\Box\Output\OutputVerbose;

class OutputVerboseTest extends TestCase
{
    public function testIsVerbose()
    {
        $output = new OutputVerbose();

        $this->assertFalse($output->isVerbose());
    }

    public function testIsVerboseEnabled()
    {
        $output = new OutputVerbose(OutputVerbose::VERBOSITY_VERBOSE);

        $this->assertTrue($output->isVerbose());
    }

    public function testVerbose()
    {
        $output = $this->getMock(
            'KevinGH\\Box\\Output\\OutputVerbose',
            array('write')
        );

        $output->expects($this->once())
               ->method('write')
               ->with(
                     $this->equalto('Hello!'),
                     $this->isFalse(),
                     $this->equalTo(0)
                 );

        $output->verbose('Test!');
        $output->setVerbosity(OutputVerbose::VERBOSITY_VERBOSE);
        $output->verbose('Hello!');
    }

    public function testVerboseln()
    {
        $output = $this->getMock(
            'KevinGH\\Box\\Output\\OutputVerbose',
            array('write')
        );

        $output->expects($this->once())
               ->method('write')
               ->with(
                   $this->equalto('Hello!'),
                   $this->isTrue(),
                   $this->equalTo(0)
                 );

        $output->verboseln('Test!');
        $output->setVerbosity(OutputVerbose::VERBOSITY_VERBOSE);
        $output->verboseln('Hello!');
    }
}