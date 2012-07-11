<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console\Helper;

    use KevinGH\Box\Box,
        KevinGH\Box\Test\TestCase,
        RuntimeException,
        Symfony\Component\Console\Helper\HelperSet,
        Symfony\Component\Process\Process;

    class ConfigTest extends TestCase
    {
        private $cwd;
        private $dir;

        protected function setUp()
        {
            parent::setUp();

            if (false === ($this->cwd = getcwd()))
            {
                if (isset($_SERVER['PWD']))
                {
                    $this->cwd = $_SERVER['PWD'];
                }

                else
                {
                    $process = new Process('cd');

                    if (0 === $process->run())
                    {
                        $this->cwd = trim($process->getOutput());
                    }

                    else
                    {
                        throw new RuntimeException('Could not get current working directory path.');
                    }
                }
            }

            $this->dir = $this->dir();

            chdir($this->dir);
        }

        protected function tearDown()
        {
            chdir($this->cwd);

            parent::tearDown();
        }

        public function testConstructorDefaults()
        {
            $config = new Config;

            $expected = array(
                'algorithm' => Box::SHA1,
                'alias' => 'default.phar',
                'base-path' => null,
                'blacklist' => array(),
                'directories' => array(),
                'files' => array(),
                'finder' => array(),
                'git-version' => null,
                'key' => null,
                'key-pass' => null,
                'main' => null,
                'output' => 'default.phar',
                'replacements' => array(),
                'stub' => null
            );

            $this->assertEquals($expected, $config->getArrayCopy());
        }

        public function testFindGiven()
        {
            $config = new Config;

            touch($this->dir . '/test.json');

            $this->assertEquals($this->dir . '/test.json', $config->find('test.json'));
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The configuration file does not exist.
         */
        public function testFindGivenInvalid()
        {
            $config = new Config;

            $config->find('test.json');
        }

        public function testFindOverride()
        {
            $config = new Config;

            touch($this->dir . '/box.json');

            $this->assertEquals($this->dir . '/box.json', $config->find(null));
        }

        public function testFindDistribution()
        {
            $config = new Config;

            touch($this->dir . '/box-dist.json');

            $this->assertEquals($this->dir . '/box-dist.json', $config->find(null));
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage No configuration file found.
         */
        public function testFindNotFound()
        {
            $config = new Config;

            $config->find(null);
        }

        public function testGetCurrentDirGetpwd()
        {
            if (false === ($cwd = getcwd()))
            {
                $this->markTestSkipped('Unable to acquire current directory path using getcwd().');

                return;
            }

            $config = new Config;

            $this->assertEquals($cwd, $config->getCurrentDir());
        }

        public function testGetCurrentDirPWD()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            if (false === isset($_SERVER['PWD']))
            {
                $_SERVER['PWD'] = '/test/nix/path';
            }

            $this->redefine('getcwd', '', 'return false;');

            $config = new Config;

            $this->assertEquals($_SERVER['PWD'], $config->getCurrentDir());

            $this->restore('getcwd');

            if ('/test/nix/path' === $_SERVER['PWD'])
            {
                unset($_SERVER['PWD']);
            }
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage Could not get current working directory path.
         */
        public function testGetCurrentDirFail()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            if (isset($_SERVER['PWD']))
            {
                $pwd = $_SERVER['PWD'];

                unset($_SERVER['PWD']);
            }

            $this->redefine('getcwd', '', 'return false;');

            $config = new Config;

            try
            {
                $config->getCurrentDir();
            }

            catch (Exception $exception)
            {
            }

            $this->restore('getcwd');

            if (isset($pwd))
            {
                $_SERVER['PWD'] = $pwd;
            }

            if (isset($exception))
            {
                throw $exception;
            }
        }

        public function testGetFiles()
        {
            $this->tree(array(
                'files' => array(
                    'one.php',
                    'two.jpg',
                    'three.php'
                ),
                'directories' => array(
                    'one' => array(
                        'one.php',
                        'two.jpg',
                        'three.gif'
                    ),
                    'two' => array(
                        'one.jpg',
                        'two.php',
                        'three.phtml'
                    ),
                    'three' => array(
                        'one.png',
                        'two.jpg',
                        'three.gif'
                    )
                )
            ), $this->dir);

            $config = new Config(array(
                'base-path' => $this->dir,
                'blacklist' => array(
                    'directories/two/three.phtml'
                ),
                'files' => array(
                    'files/one.php',
                    'files/three.php'
                ),
                'directories' => array(
                    'directories/one',
                    'directories/two',
                    'directories/three'
                ),
                'finder' => array(
                    array(
                        'name' => '*.php',
                        'in' => 'directories/one'
                    ),
                    array(
                        'name' => array('*.php', '*.phtml'),
                        'in' => array(
                            'directories/one',
                            'directories/two',
                            'directories/three'
                        )
                    )
                )
            ));

            $result = $config->getFiles();

            sort($result);

            $expected = array(
                "{$this->dir}/directories/one/one.php",
                "{$this->dir}/directories/two/two.php",
                "{$this->dir}/files/one.php",
                "{$this->dir}/files/three.php"
            );

            $this->assertEquals($expected, $result);
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The path is not a file: /does/not/exist
         */
        public function testGetFilesInvalidFile()
        {
            $config = new Config(array(
                'files' => array('/does/not/exist')
            ));

            $config->getFiles();
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage Invalid Finder setting: badMethod
         */
        public function testGetFilesInvalidFinderMethod()
        {
            $config = new Config(array(
                'finder' => array(
                    array(
                        'badMethod' => true
                    )
                )
            ));

            $config->getFiles();
        }

        public function testGitCommit()
        {
            $this->command('git init');
            $this->command('touch test');
            $this->command('git add test');
            $this->command('git commit test -m "test" --author="Test <test@test.com>"');

            $config = new Config;

            $this->assertRegExp('/^[a-f0-9]{7}+$/', $config->getGitCommit());
        }

        public function testGitCommitNotRepo()
        {
            $config = new Config;

            $this->assertNull($config->getGitCommit());
        }

        public function testGitTag()
        {
            $this->command('git init');
            $this->command('touch test');
            $this->command('git add test');
            $this->command('git commit test -m "test" --author="Test <test@test.com>"');
            $this->command('git tag v1.0-ALPHA1');

            $config = new Config;

            $this->assertEquals('v1.0-ALPHA1', $config->getGitTag());
        }

        public function testGitTagNotRepo()
        {
            $config = new Config;

            $this->assertNull($config->getGitTag());
        }

        public function testGetHelper()
        {
            $config = new Config;

            $this->assertNull($config->getHelperSet());
        }

        public function testGetName()
        {
            $config = new Config;

            $this->assertEquals('config', $config->getName());
        }

        public function testLoad()
        {
            $this->command('git init');
            $this->command('touch test');
            $this->command('git add test');
            $this->command('git commit test -m "test" --author="Test <test@test.com>"');
            $this->command('git tag v1.0-ALPHA1');

            $file = $this->dir . '/test.json';

            file_put_contents($file, utf8_encode(json_encode(array(
                'algorithm' => 'SHA256',
                'git-version' => 'git_version'
            ))));

            $config = new Config;

            $config->load($file);

            $this->assertEquals(Box::SHA256, $config['algorithm']);
            $this->assertEquals('v1.0-ALPHA1', $config['replacements']['git_version']);
        }

        public function testLoadNull()
        {
            $file = $this->file();

            $config = new Config;

            $config->load($file);

            $this->assertNull($config['main']);
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The configuration file could not be read:
         */
        public function testLoadReadError()
        {
            $config = new Config;

            $config->load('test.json');
        }

        /**
         * @expectedException KevinGH\Box\Console\Exception\JSONException
         * @expectedExceptionMessage Syntax error
         */
        public function testLoadParseError()
        {
            $file = $this->file('{');

            $config = new Config;

            $config->load($file);
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage Invalid algorithm constant: Phar::INVALID
         */
        public function testLoadInvalidAlgo()
        {
            $file = $this->file(utf8_encode(json_encode(array(
                'algorithm' => 'INVALID'
            ))));

            $config = new Config;

            $config->load($file);
        }

        public function testRelativeOf()
        {
            $config = new Config(array(
                'base-path' => $this->dir
            ));

            $this->assertEquals('test.json', $config->relativeOf($this->dir . '/test.json'));
        }

        public function testSetHelperSet()
        {
            $config = new Config;

            $helperSet = new HelperSet;

            $config->setHelperSet($helperSet);

            $this->assertSame($helperSet, $config->getHelperSet());
        }
    }