<?php

namespace KevinGH\Box\Tests\Command\Key;

use KevinGH\Box\Command\Key\Create;
use KevinGH\Box\Test\CommandTestCase;
use KevinGH\Box\Test\FixedResponse;

class CreateTest extends CommandTestCase
{
    public function testExecute()
    {
        $this->app->getHelperSet()->set(new FixedResponse('test'));

        $tester = $this->getTester();
        $tester->execute(array(
            'command' => 'key:create',
            '--bits' => 512,
            '--out' => 'test.key',
            '--public' => 'test.pub',
            '--prompt' => true
        ));

        $this->assertRegExp('/PRIVATE KEY/', file_get_contents('test.key'));
        $this->assertRegExp('/PUBLIC KEY/', file_get_contents('test.pub'));
    }

    protected function getCommand()
    {
        return new Create();
    }
}