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

    use KevinGH\Box\Box,
        KevinGH\Box\Console\Application,
        PHPUnit_Framework_TestCase,
        Symfony\Component\Console\Helper\DialogHelper,
        Symfony\Component\Console\Output\OutputInterface,
        Symfony\Component\Console\Tester\CommandTester,
        Symfony\Component\Process\PhpProcess;

    class VerifyTest extends PHPUnit_Framework_TestCase
    {
        const TYPE_BROKEN = 3;
        const TYPE_SIGNED = 1;
        const TYPE_UNSIGNED = 2;

        private $app;
        private $command;
        private $box;
        private $dir;
        private $file;
        private $pwd;
        private $tester;

        protected function setUp()
        {
            $this->pwd = getcwd();

            $this->app = new Application;

            $this->command = $this->app->find('verify');

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

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The PHAR does not exist.
         */
        public function testExecuteInvalid()
        {
            $this->tester->execute(array(
                'command' => 'verify',
                'phar' => $this->dir . '/default.phar'
            ));
        }

        public function testExecuteBroken()
        {
            $this->makeApp(self::TYPE_BROKEN);

            $this->tester->execute(array(
                'command' => 'verify',
                'phar' => $this->dir . '/default.phar'
            ));

            $this->assertRegExp('/The PHAR failed verification/', $this->tester->getDisplay());
        }

        /**
         * @expectedException UnexpectedValueException
         */
        public function testExecuteBrokenVerbose()
        {
            $this->makeApp(self::TYPE_BROKEN);

            $this->tester->execute(array(
                'command' => 'verify',
                'phar' => $this->dir . '/default.phar'
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        }

        public function testExecuteSigned()
        {
            if (extension_loaded('openssl'))
            {
                $this->makeApp(self::TYPE_SIGNED);

                $this->tester->execute(array(
                    'command' => 'verify',
                    'phar' => $this->dir . '/default.phar'
                ));

                $this->assertRegExp('/The PHAR is verified and signed/', $this->tester->getDisplay());
            }

            else
            {
                $this->markTestSkipped('The openssl extension is not available.');
            }
        }

        public function testExecuteUnsigned()
        {
            $this->makeApp(self::TYPE_UNSIGNED);

            $this->tester->execute(array(
                'command' => 'verify',
                'phar' => $this->dir . '/default.phar'
            ));

            $this->assertRegExp('/The PHAR is verified but not signed/', $this->tester->getDisplay());
        }

        private function makeApp($type = 0)
        {
            $key = '';

            if (self::TYPE_SIGNED === $type)
            {
                $this->createKey($key = $this->dir . '/test.key');

                $key = <<<KEY

    \$box->usePrivateKeyFile('$key');

KEY
                ;
            }

            $class = __DIR__ . '/../../../../../lib/KevinGH/Box/Box.php';

            $process = new PhpProcess(<<<SOURCE
<?php

    require '$class';

    \$box = new KevinGH\Box\Box('{$this->dir}/default.phar');

    \$box->importSource('class.php', <<<INNER
<?php

    class TestClass
    {
        public static function test()
        {
            echo "Success!\n";
        }
    }
INNER
    );

     \$box->importSource('main.php', <<<INNER
#!/usr/bin/env php
<?php

    require 'phar://default.phar/class.php';

    TestClass::test();
INNER
     , true);

    \$box->setStub(\$box->createStub());
$key
SOURCE
            , $this->dir);

            if (0 !== $process->run())
            {
                fputs(STDERR, $process->getErrorOutput());
            }

            if (self::TYPE_BROKEN === $type)
            {
                file_put_contents(
                    $this->dir . '/default.phar',
                    preg_replace(
                        '/class/',
                        'klass',
                        file_get_contents($this->dir . '/default.phar'),
                        1
                    )
                );
            }
        }

        private function createKey($file, $pass = null)
        {
            $resource = openssl_pkey_new();

            openssl_pkey_export($resource, $key, $pass);

            file_put_contents($file, $key);

            $public = openssl_pkey_get_details($resource);
            $public = $public['key'];

            openssl_pkey_free($resource);

            return array($key, $public);
        }
    }