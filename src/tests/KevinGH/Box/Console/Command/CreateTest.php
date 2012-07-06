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

    use KevinGH\Box\Console\Application,
        PHPUnit_Framework_TestCase,
        Symfony\Component\Console\Output\OutputInterface,
        Symfony\Component\Console\Tester\CommandTester,
        Symfony\Component\Process\Process;

    class CreateTest extends PHPUnit_Framework_TestCase
    {
        private $app;
        private $command;
        private $dir;
        private $file;
        private $pwd;
        private $tester;

        protected function setUp()
        {
            $this->pwd = getcwd();

            $this->app = new Application;

            $this->command = $this->app->find('create');

            $this->tester = new CommandTester($this->command);

            unlink($this->dir = tempnam(sys_get_temp_dir(), 'bxt'));

            mkdir($this->dir);

            chdir($this->dir);

            touch($this->file = $this->dir . '/box.json');
        }

        protected function tearDown()
        {
            chdir($this->pwd);

            rmdir_r($this->dir);
        }

        public function testExecute()
        {
            file_put_contents($this->dir . '/TestClass.php', <<<SOURCE
<?php

    class TestClass
    {
        public static function test()
        {
            echo "Success!\nVersion: @package_version@\n";
        }
    }
SOURCE
            );

            file_put_contents($this->dir . '/main.php', <<<SOURCE
<?php

    require 'phar://default.phar/TestClass.php';

    TestClass::test();
SOURCE
            );

            $version = "Version: @package_version@\n";

            $make = new Process('git init', $this->dir);

            if (0 === $make->run())
            {
                $this->command('git add .');
                $this->command('git commit -a --author="Test <test@test.com>" -m "Adding test files."');
                $this->command('git tag TEST-999');

                $version = "Version: TEST-999\n";
            }

            file_put_contents($this->file, utf8_encode(json_encode(array(
                'git-version' => (false === strpos($version, '@')) ? 'package_version' : null,
                'files' => 'TestClass.php',
                'main' => 'main.php',
                'stub' => true
            ))));

            $this->tester->execute(array(
                'command' => 'create',
                '--config' => $this->file
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));

            $this->assertEquals(<<<EXPECTED
Adding files...
    - TestClass.php

EXPECTED
            , $this->tester->getDisplay());

            $phar = new Process('php ' . escapeshellarg($this->dir . '/default.phar'));

            $this->assertEquals(0, $phar->run());
            $this->assertEquals("Success!\n$version", $phar->getOutput());
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The main file does not exist.
         */
        public function testExecuteInvalidMain()
        {
            file_put_contents($this->file, utf8_encode(json_encode(array(
                'main' => 'main.php'
            ))));

            $this->tester->execute(array(
                'command' => 'create',
                '--config' => $this->file
            ));
        }

        public function testExecuteStubAlt()
        {
            file_put_contents($this->dir . '/nothing.php', '<?php class Nothing {}');

            file_put_contents($this->dir . '/stub.php', <<<SOURCE
#!/usr/bin/env php
<?php

    echo "Success!\n";

    __HALT_COMPILER();
SOURCE
            );

            file_put_contents($this->file, utf8_encode(json_encode(array(
                'files' => 'nothing.php',
                'stub' => 'stub.php'
            ))));

            $this->tester->execute(array(
                'command' => 'create',
                '--config' => $this->file
            ));

            $phar = new Process('php ' . escapeshellarg($this->dir . '/default.phar'));

            $this->assertEquals(0, $phar->run());
            $this->assertEquals("Success!\n", $phar->getOutput());
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The stub file does not exist.
         */
        public function testExecuteStubInvalid()
        {
            file_put_contents($this->file, utf8_encode(json_encode(array(
                'stub' => 'stub.php'
            ))));

            $this->tester->execute(array(
                'command' => 'create',
                '--config' => $this->file
            ));
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The stub file could not be read:
         */
        public function testExecuteStubReadFail()
        {
            file_put_contents($this->file, utf8_encode(json_encode(array(
                'stub' => '/root'
            ))));

            $this->tester->execute(array(
                'command' => 'create',
                '--config' => $this->file
            ));
        }

        private function command($command)
        {
            $process = new Process($command, $this->dir);

            if (0 !== $process->run())
            {
                throw new RuntimeException("The command failed: $command");
            }
        }
    }