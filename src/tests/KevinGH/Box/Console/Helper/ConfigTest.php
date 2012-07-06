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

    use PHPUnit_Framework_TestCase,
        Symfony\Component\Console\Helper\HelperSet,
        Symfony\Component\Process\Process;

    class ConfigTest extends PHPUnit_Framework_TestCase
    {
        private $config;
        private $dir;
        private $temp;

        protected function setUp()
        {
            $this->config = new Config;

            $this->dir = getcwd();

            unlink($this->temp = tempnam(sys_get_temp_dir(), 'bxt'));

            mkdir($this->temp);

            chdir($this->temp);
        }

        protected function tearDown()
        {
            chdir($this->dir);

            rmdir_r($this->temp);
        }

        public function testFindGiven()
        {
            touch($this->temp . '/test.json');

            $this->assertEquals($this->temp . '/test.json', $this->config->find('test.json'));
        }

        public function testFindUser()
        {
            touch($this->temp . '/box-dist.json');
            touch($this->temp . '/box.json');

            $this->assertEquals($this->temp . '/box.json', $this->config->find(''));
        }

        public function testFindDist()
        {
            touch($this->temp . '/box-dist.json');

            $this->assertEquals($this->temp . '/box-dist.json', $this->config->find(''));
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The configuration file does not exist.
         */
        public function testFindInvalid()
        {
            $this->config->find('test.json');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage No configuration file found.
         */
        public function testFindNone()
        {
            $this->config->find('');
        }

        public function testGetFiles()
        {
            build_paths(array(
                $this->temp => array(
                    'files' => array(
                        'one.php',
                        'one.jpg',
                        'two.php',
                        'two.jpg'
                    ),
                    'dirs' => array(
                        'one' => array(
                            'test.php',
                            'test.jpg'
                        ),
                        'two' => array(
                            'test.php',
                            'test.jpg',
                            'sub' => array(
                                'test.php',
                                'test.jpg'
                            )
                        )
                    ),
                    'finder' => array(
                        'one' => array(
                            'finder.php',
                            'finder.jpg'
                        ),
                        'two' => array(
                            'finder.php',
                            'finder.php5',
                            'finder.jpg',
                            'sub' => array(
                                'finder.php',
                                'finder.jpg'
                            )
                        ),
                        'three' => array(
                            'finder.html',
                            'finder.jpg',
                            'finder.php4'
                        )
                    )
                )
            ));

            $this->config['files'] = array(
                'files/one.php',
                'files/two.jpg'
            );

            $this->config['directories'] = array(
                'dirs/one',
                'dirs/two'
            );

            $this->config['finder'] = array(
                array(
                    'name' => '*.php',
                    'in' => 'finder/one'
                ),
                array(
                    'name' => array('*.php', '*.php5'),
                    'in' => array(
                        'finder/two',
                        'finder/three'
                    )
                )
            );

            $expected = array(
                $this->temp . '/dirs/one/test.php',
                $this->temp . '/dirs/two/sub/test.php',
                $this->temp . '/dirs/two/test.php',
                $this->temp . '/files/one.php',
                $this->temp . '/files/two.jpg',
                $this->temp . '/finder/one/finder.php',
                $this->temp . '/finder/two/finder.php',
                $this->temp . '/finder/two/finder.php5',
                $this->temp . '/finder/two/sub/finder.php',
            );

            $files = $this->config->getFiles();

            sort($files);

            $this->assertEquals($expected, $files);
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The path is not a file:
         */
        public function testGetFilesNotFile()
        {
            $this->config['files'] = array($this->temp);

            $this->config->getFiles();
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage Invalid Finder setting: badSetting
         */
        public function testGetFilesBadFinderSetting()
        {
            $this->config['finder'] = array(array(
                'badSetting' => 'test'
            ));

            $this->config->getFiles();
        }

        public function testGetGitCommit()
        {
            $this->config['base-path'] = $this->temp;

            $make = new Process('git init', $this->temp);

            if (0 === $make->run())
            {
                touch($this->temp . '/file');

                $this->command('git add file');
                $this->command('git config user.name Test');
                $this->command('git config user.email test@test.com');
                $this->command('git commit -m "Adding test file."');

                $this->assertRegExp('/^[a-f0-9]{7}$/', $this->config->getGitCommit());
            }

            else
            {
                $this->markTestSkipped('Unable to create Git repository.');
            }
        }

        public function testGetGitCommitFail()
        {
            $this->config['base-path'] = '/does/not/exist';

            $this->assertNull($this->config->getGitCommit());
        }

        public function testGetGitTag()
        {
            $this->config['base-path'] = $this->temp;

            $make = new Process('git init', $this->temp);

            if (0 === $make->run())
            {
                touch($this->temp . '/file');

                $this->command('git add file');
                $this->command('git config user.name Test');
                $this->command('git config user.email test@test.com');
                $this->command('git commit -m "Adding test file."');
                $this->command('git tag TEST-01');

                $this->assertEquals('TEST-01', $this->config->getGitTag());
            }

            else
            {
                $this->markTestSkipped('Unable to create Git repository.');
            }
        }

        public function testGetGitTagFail()
        {
            $this->config['base-path'] = '/does/not/exist';

            $this->assertNull($this->config->getGitTag());
        }

        public function testGetHelperSet()
        {
            $this->assertNull($this->config->getHelperSet());
        }

        public function testGetName()
        {
            $this->assertEquals('config', $this->config->getName());
        }

        public function testLoad()
        {
            $make = new Process('git init', $this->temp);

            if (0 === $make->run())
            {
                $this->config['git-version'] = 'git_version';
            }

            file_put_contents($this->temp . '/box-dist.json', utf8_encode(json_encode(array(
                'output' => 'test.phar',
                'replacements' => array(
                    'rand' => $rand = rand()
                )
            ))));

            $this->config->load('box-dist.json');

            $this->assertEquals('default.phar', $this->config['alias']);
            $this->assertEquals('test.phar', $this->config['output']);
            $this->assertEquals(array(
                'rand' => $rand,
                'git_version' => $this->config->getGitCommit()
            ), $this->config['replacements']);
        }

        public function testLoadBlank()
        {
            touch($this->temp . '/box.json');

            $this->config->load('box.json');

            $this->assertEquals('default.phar', $this->config['output']);
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The configuration file could not be read:
         */
        public function testLoadReadFail()
        {
            $this->config->load('box.json');
        }

        /**
         * @expectedException KevinGH\Box\Console\Exception\JSONException
         * @expectedExceptionMessage Syntax error
         */
        public function testLoadParseFail()
        {
            file_put_contents($this->temp . '/box.json', '{');

            $this->config->load('box.json');
        }

        public function testRelativeOf()
        {
            $this->config['base-path'] = $this->temp;

            $this->assertEquals('test.php', $this->config->relativeOf($this->temp . '/test.php'));
        }

        /**
         * @depends testGetHelperSet
         */
        public function testSetHelper()
        {
            $helper = new HelperSet;

            $this->config->setHelperSet($helper);

            $this->assertSame($helper, $this->config->getHelperSet());
        }

        private function command($command)
        {
            $process = new Process($command, $this->config['base-path']);

            if (0 !== $process->run())
            {
                throw new RuntimeException("The command failed: $command");
            }
        }
    }