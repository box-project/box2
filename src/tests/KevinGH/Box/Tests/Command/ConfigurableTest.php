<?php

namespace KevinGH\Box\Tests\Command;

use KevinGH\Box\Command\Configurable;
use KevinGH\Box\Test\CommandTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class ConfigurableTest extends CommandTestCase
{
    /**
     * @var Configurable
     */
    private $command;

    public function testConfigure()
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('configuration'));
    }

    public function testGetConfig()
    {
        file_put_contents('box.json', '{}');

        $input = new ArrayInput(array());
        $input->bind($this->command->getDefinition());

        $this->assertInstanceOf(
            'KevinGH\\Box\\Configuration',
            $this->callMethod($this->command, 'getConfig', array($input))
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->app->add(new TestConfigurable('test'));

        $this->command = $this->app->get('test');
    }
}

class TestConfigurable extends Configurable
{
}