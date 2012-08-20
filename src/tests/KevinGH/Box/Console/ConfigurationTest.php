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

use KevinGH\Box\Box;
use KevinGH\Box\Console\Helper;
use KevinGH\Box\Test\TestCase;
use KevinGH\Elf;
use Symfony\Component\Console\Helper\HelperSet;

class ConfigurationTest extends TestCase
{
    private $config;
    private $file;
    private $helpers;

    protected function setUp()
    {
        parent::setUp();

        $this->file = tempnam($this->currentDir, 'box');
        $this->helpers = new HelperSet(array(
            new Helper\Box(),
            new Elf\Git(),
            new Elf\Json()
        ));
        $this->config = new Configuration($this->helpers, $this->file);
    }

    public function testConstructor()
    {
        $file = $this->property($this->config, 'file');
        $helpers = $this->property($this->config, 'helpers');

        $this->assertEquals($this->file, $file());
        $this->assertSame($this->helpers, $helpers());
        $this->assertEquals(
            array(
                'algorithm' => Box::SHA1,
                'alias' => 'default.phar',
                'base-path' => $this->currentDir,
                'blacklist' => array(),
                'chmod' => null,
                'compression' => null,
                'directories' => array(),
                'directories-bin' => array(),
                'files' => array(),
                'files-bin' => array(),
                'finder' => array(),
                'finder-bin' => array(),
                'git-version' => null,
                'intercept' => false,
                'key' => null,
                'key-pass' => null,
                'main' => null,
                'metadata' => null,
                'output' => 'default.phar',
                'replacements' => array(),
                'stub' => null
            ),
            $this->config->getArrayCopy()
        );
    }

    public function testGetFiles()
    {
        mkdir('base/src/finder', 0755, true);
        mkdir('base/src/test');

        file_put_contents('base/src/finder/test.html', '');
        file_put_contents('base/src/finder/test.php', '');
        file_put_contents('base/src/test.html', '');
        file_put_contents('base/src/test.php', '');
        file_put_contents('base/src/test/test.html', '');
        file_put_contents('base/src/test/test.php', '');

        $this->config['base-path'] = realpath('base');
        $this->config['blacklist'] = 'src/test.html';
        $this->config['directories'] = 'src/test';
        $this->config['files'] = array(
            'src/test.html',
            'src/test.php'
        );
        $this->config['finder'] = array(
            array(
                'name' => '*.php',
                'in' => 'src/finder'
            )
        );

        $expected = array(
            'src/test.php' => realpath('base/src/test.php'),
            'src/test/test.php' => realpath('base/src/test/test.php'),
            'src/finder/test.php' => realpath('base/src/finder/test.php')
        );

        $this->assertEquals($expected, $this->config->getFiles());
    }

    public function testGetFilesBinary()
    {
        mkdir('base/src/finder', 0755, true);
        mkdir('base/src/test');

        file_put_contents('base/src/finder/test.html', '');
        file_put_contents('base/src/finder/test.php', '');
        file_put_contents('base/src/finder/test.png', '');
        file_put_contents('base/src/test.html', '');
        file_put_contents('base/src/test.png', '');
        file_put_contents('base/src/test/test.html', '');
        file_put_contents('base/src/test/test.png', '');

        $this->config['base-path'] = realpath('base');
        $this->config['directories-bin'] = 'src/test';
        $this->config['files-bin'] = array(
            'src/test.html',
            'src/test.png'
        );
        $this->config['finder-bin'] = array(
            array(
                'name' => '*.png',
                'in' => 'src/finder'
            )
        );

        $expected = array(
            'src/test.html' => realpath('base/src/test.html'),
            'src/test.png' => realpath('base/src/test.png'),
            'src/test/test.html' => realpath('base/src/test/test.html'),
            'src/test/test.png' => realpath('base/src/test/test.png'),
            'src/finder/test.png' => realpath('base/src/finder/test.png')
        );

        $this->assertEquals($expected, $this->config->getFiles(true));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The path "does/not/exist" is not a file or does not exist.
     */
    public function testGetFilesInvalidPath()
    {
        $this->config['files'] = 'does/not/exist';

        $this->config->getFiles();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The method "invalidMethod" was not found in the Finder class.
     */
    public function testGetFilesInvalidFinderMethod()
    {
        $this->config['finder'] = array(array('invalidMethod' => true));

        $this->config->getFiles();
    }

    public function testGetMainPathRelative()
    {
        $this->assertNull($this->config->getMainPath());

        mkdir('base/bin', 0755, true);

        file_put_contents('base/bin/main', '');

        $this->config['base-path'] = realpath('base');
        $this->config['main'] = 'bin/main';

        $this->assertEquals(realpath('base/bin/main'), $this->config->getMainPath());
    }

    public function testGetMainPathAbsolute()
    {
        $this->config['main'] = $file = $this->file();

        $this->assertEquals($file, $this->config->getMainPath());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The path "bin/main" is not a file or it does not exist.
     */
    public function testGetMainPathInvalidPath()
    {
        $this->config['main'] = 'bin/main';

        $this->config->getMainPath();
    }

    public function testGetOutputPathRelative()
    {
        $this->assertEquals(
            getcwd() . DIRECTORY_SEPARATOR . 'default.phar',
            $this->config->getOutputPath()
        );
    }

    public function testGetOutputPathAbsolute()
    {
        $this->config['output'] = $file = $this->file();

        $this->assertEquals($file, $this->config->getOutputPath());
    }

    public function testGetPrivateKeyPathNotSet()
    {
        $this->assertNull($this->config->getPrivateKeyPath());
    }

    public function testGetPrivateKeyPathSet()
    {
        $this->config['key'] = 'private.key';

        $this->assertEquals(
            $this->config['base-path'] . DIRECTORY_SEPARATOR . $this->config['key'],
            $this->config->getPrivateKeyPath()
        );
    }

    public function testGetPrivateKeyAbsolute()
    {
        $this->config['key'] = $file = $this->file();

        $this->assertEquals($file, $this->config->getPrivateKeyPath());
    }

    public function testProcessConfig()
    {
        file_put_contents('test', '');

        $this->command('git init');
        $this->command('git add test');
        $this->command('git commit -m "Adding test."');
        $this->command('git tag 1.0.0');

        $config = new Configuration($this->helpers, $this->file, array(
            'algorithm' => 'MD5',
            'compression' => 'GZ',
            'git-version' => 'package_version',
            'replacements' => (object) array('rand' => $rand = rand())
        ));

        $this->assertEquals(Box::MD5, $config['algorithm']);
        $this->assertEquals(Box::GZ, $config['compression']);
        $this->assertEquals($rand, $config['replacements']['rand']);
        $this->assertEquals('1.0.0', $config['replacements']['package_version']);
    }

    public function testLoad()
    {
        $config = Configuration::load($this->helpers, $this->getResource('tests/box-example.json'));

        $this->assertInstanceOf('KevinGH\Box\Console\Configuration', $config);
        $this->assertEquals(Box::MD5, $config['algorithm']);
        $this->assertEquals('test.phar', $config['alias']);
        $this->assertEquals(Box::GZ, $config['compression']);
        $this->assertEquals('test.phar', $config['output']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid signature algorithm constant: Phar::INVALID
     */
    public function testProcessConfigInvalidAlgorithm()
    {
        $config = new Configuration($this->helpers, $this->file, array(
            'algorithm' => 'INVALID'
        ));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid compression algorithm constant: Phar::INVALID
     */
    public function testProcessConfigInvalidCompression()
    {
        $config = new Configuration($this->helpers, $this->file, array(
            'compression' => 'INVALID'
        ));
    }
}

