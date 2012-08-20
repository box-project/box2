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

use KevinGH\Box\Test\TestCase;

class ApplicationTest extends TestCase
{
    private $app;

    protected function setUp()
    {
        parent::setUp();

        $this->app = new Application();
    }

    public function testConstructor()
    {
        $this->assertEquals('Box', $this->app->getName());
        $this->assertEquals('@package_version@', $this->app->getVersion());
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage Test error.
     */
    public function testConstructorErrorHandler()
    {
        trigger_error('Test error.', E_USER_ERROR);
    }

    public function testCreateOutput()
    {
        $output = $this->app->createOutput();

        $this->assertInstanceOf(
            'Symfony\Component\Console\Output\ConsoleOutput',
            $output
        );
    }

    /**
     * @dataProvider getExpectedStyles
     */
    public function testCreateOutputHasStyle($style)
    {
        $this->assertTrue($this->app->createOutput()->getFormatter()->hasStyle($style));
    }

    public function getExpectedStyles()
    {
        return array(
            array('prefix')
        );
    }
}

