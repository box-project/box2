<?php

namespace KHerGe\Box\Tests\Unit\Helper;

use KHerGe\Box\Helper\ConfigHelper;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Performs unit testing on `ConfigHelper`.
 *
 * @see KHerGe\Box\Helper\ConfigHelper
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ConfigHelperTest extends TestCase
{
    /**
     * The configuration helper instance being tested.
     *
     * @var ConfigHelper
     */
    private $helper;

    /**
     * The temporary directory path.
     *
     * @var string
     */
    private $dir;

    /**
     * The previous directory path.
     *
     * @var string
     */
    private $previous;

    /**
     * The temporary file manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * Make sure the expected helper name is returned.
     */
    public function testGetName()
    {
        $this->assertEquals(
            'config',
            $this->helper->getName(),
            'The expected helper name should be returned.'
        );
    }

    /**
     * Make sure that we can load the default configuration file.
     */
    public function testLoad()
    {
        file_put_contents("{$this->dir}/box.json.dist", '{"compression":"GZ"}');

        $this->assertEquals(
            array(
                'compression' => 'GZ',
                'mode' => 644,
                'output' => 'output.phar',
                'signature' => array(
                    'type' => 'SHA1',
                ),
                'sources' => array(
                    'base' => $this->dir,
                    'dirs' => array(),
                    'files' => array(),
                ),
            ),
            $this->helper->load(),
            'The distribution configuration file should be loaded.'
        );

        file_put_contents("{$this->dir}/box.json", '{"git":"version","sources":null}');

        $this->assertEquals(
            array(
                'git' => array(
                    'replace' => 'version',
                    'path' => $this->dir,
                    'value' => 'tag/commit',
                ),
                'sources' => array(
                    'base' => $this->dir,
                    'dirs' => array(),
                    'files' => array(),
                ),
                'compression' => 'NONE',
                'mode' => 644,
                'output' => 'output.phar',
                'signature' => array(
                    'type' => 'SHA1',
                ),
            ),
            $this->helper->load(),
            'The user configuration file should be loaded.'
        );
    }

    /**
     * Make sure that we can load from a different directory.
     */
    public function testLoadDir()
    {
        $dir = $this->temp->createDir();

        file_put_contents("$dir/box.json", '{}');

        $this->assertEquals(
            array(
                'compression' => 'NONE',
                'mode' => 644,
                'output' => 'output.phar',
                'signature' => array(
                    'type' => 'SHA1',
                ),
                'sources' => array(
                    'base' => $dir,
                    'dirs' => array(),
                    'files' => array(),
                ),
            ),
            $this->helper->load($dir),
            'The default configuration file should be loaded.'
        );
    }

    /**
     * Make sure that we can specify the file to load.
     */
    public function testLoadFile()
    {
        file_put_contents("{$this->dir}/alt.json", '{}');

        $this->assertEquals(
            array(
                'compression' => 'NONE',
                'mode' => 644,
                'output' => 'output.phar',
                'signature' => array(
                    'type' => 'SHA1',
                ),
                'sources' => array(
                    'base' => $this->dir,
                    'dirs' => array(),
                    'files' => array(),
                ),
            ),
            $this->helper->load('alt.json'),
            'The default configuration file should be loaded.'
        );
    }

    /**
     * Make sure that an invalid path throws an exception.
     */
    public function testLoadInvalidPath()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The path "/should/not/exist" does not exist.'
        );

        $this->helper->load('/should/not/exist');
    }

    /**
     * Make sure that an exception is thrown if the default file could not be found.
     */
    public function testLoadDefaultMissing()
    {
        $this->setExpectedException(
            'RuntimeException',
            "The directory \"{$this->dir}\" did not contain the configuration file."
        );

        $this->helper->load();
    }

    /**
     * Make sure that an exception is thrown for too many default .dist files.
     */
    public function testLoadManyDist()
    {
        touch("{$this->dir}/box.json.dist");
        touch("{$this->dir}/box.yaml.dist");

        $this->setExpectedException(
            'RuntimeException',
            "Many configuration files were found in \"{$this->dir}\". You will need to specify the one you want to use."
        );

        $this->helper->load();
    }

    /**
     * Make sure that an exception is thrown for too many default files.
     */
    public function testLoadManyNormal()
    {
        touch("{$this->dir}/box.json");
        touch("{$this->dir}/box.yaml");

        $this->setExpectedException(
            'RuntimeException',
            "Many configuration files were found in \"{$this->dir}\". You will need to specify the one you want to use."
        );

        $this->helper->load();
    }

    /**
     * @override
     */
    protected function setUp()
    {
        $this->helper = new ConfigHelper();
        $this->previous = realpath('.');
        $this->temp = new Temp();
        $this->dir = $this->temp->createDir();

        chdir($this->dir);
    }

    /**
     * @override
     */
    protected function tearDown()
    {
        chdir($this->previous);

        $this->temp->purgePaths();
    }
}
