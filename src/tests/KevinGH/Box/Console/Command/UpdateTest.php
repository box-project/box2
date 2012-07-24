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

    use Exception,
        KevinGH\Box\Test\CommandTestCase;

    class UpdateTest extends CommandTestCase
    {
        const COMMAND = 'update';

        private $url;
        private $self;

        protected function setUp($name = 'Box', $version = '@git_version@')
        {
            parent::setUp($name, '1.0.0');

            $extract = $this->property($this->command, 'extract');
            $extract('/box\\-(.+?)\\.phar/');

            $matcher = $this->property($this->command, 'match');
            $matcher('/box\\-(.+?)\\.phar/');

            $url = $this->property($this->command, 'url');
            $url($this->url = $this->dir());

            $this->self = $_SERVER['argv'][0];

            $_SERVER['argv'][0] = $this->file();
        }

        protected function tearDown()
        {
            $_SERVER['argv'][0] = $this->self;
        }

        public function testIntegrityCheck()
        {
            file_put_contents($this->url . '/downloads', utf8_encode(json_encode(array(
                array(
                    'name' => 'box-1.0.1.phar',
                    'html_url' => $this->resource('test.phar', true)
                )
            ))));

            $this->tester->execute(array(
                'command' => self::COMMAND
            ));

            $this->assertEquals(
                "Update successful!\n",
                $this->tester->getDisplay()
            );
        }

        public function testIntegrityCheckFail()
        {
            file_put_contents($this->url . '/downloads', utf8_encode(json_encode(array(
                array(
                    'name' => 'box-1.0.1.phar',
                    'html_url' => $this->file(str_replace(
                        '__HALT_COMPILER',
                        '',
                        $this->resource('test.phar')
                    ))
                )
            ))));

            try
            {
                $this->tester->execute(array(
                    'command' => self::COMMAND
                ));
            }

            catch (Exception $exception)
            {
            }

            $this->assertEquals(
                "The update was corrupted.\n\n",
                $this->tester->getDisplay()
            );

            $this->assertInstanceOf('UnexpectedValueException', $exception);
        }
    }