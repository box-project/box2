<?php

namespace KHerGe\Box\Tests\Functional\Config;

use KHerGe\Box\Config\Definition;
use Phar;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Performs functional testing on `Definition`.
 *
 * @see KHerGe\Box\Config\Definition
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class DefinitionTest extends TestCase
{
    /**
     * The definition instance being tested.
     *
     * @var Definition
     */
    private $definition;

    /**
     * The configuration processor.
     *
     * @var Processor
     */
    private $processor;

    /**
     * Make sure that the default empty source settings are set as expected.
     */
    public function testDefaultEmptySource()
    {
        $expected = array(
            'sources' => array(
                'base' => null,
                'dirs' => array(),
                'files' => array(),
            ),
            'compression' => 'NONE',
            'mode' => 644,
            'output' => 'output.phar',
        );

        $sources = array(
            'sources' => array()
        );

        $this->assertEquals(
            $expected,
            $this->processor->processConfiguration(
                $this->definition,
                array($sources)
            )
        );
    }

    /**
     * Make sure that the default empty stub settings are set as expected.
     */
    public function testDefaultEmptyStub()
    {
        $expected = array(
            'stub' => array(
                'web' => array(
                    'alias' => null,
                    'index' => 'index.php',
                    'not_found' => null,
                    'mime' => array(
                        'avi' => 'video/avi',
                        'bmp' => 'image/bmp',
                        'c' => 'text/plain',
                        'c++' => 'text/plain',
                        'cc' => 'text/plain',
                        'cpp' => 'text/plain',
                        'css' => 'text/css',
                        'dtd' => 'text/plain',
                        'gif' => 'image/gif',
                        'h' => 'text/plain',
                        'htm' => 'text/html',
                        'html' => 'text/html',
                        'htmls' => 'text/html',
                        'ico' => 'image/x-ico',
                        'inc' => Phar::PHP,
                        'jpe' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'jpg' => 'image/jpeg',
                        'js' => 'application/x-javascript',
                        'log' => 'text/plain',
                        'mid' => 'audio/midi',
                        'midi' => 'audio/midi',
                        'mod' => 'audio/mod',
                        'mov' => 'movie/quicktime',
                        'mp3' => 'audio/mp3',
                        'mpeg' => 'video/mpeg',
                        'mpg' => 'video/mpeg',
                        'pdf' => 'application/pdf',
                        'php' => Phar::PHP,
                        'phps' => Phar::PHPS,
                        'png' => 'image/png',
                        'rng' => 'text/plain',
                        'swf' => 'application/shockwave-flash',
                        'tif' => 'image/tiff',
                        'tiff' => 'image/tiff',
                        'txt' => 'text/plain',
                        'wav' => 'audio/wav',
                        'xbm' => 'image/xbm',
                        'xml' => 'text/xml',
                        'xsd' => 'text/plain',
                    ),
                    'rewrite' => null,
                ),
                'require' => array(),
                'source' => array(),
                'intercept' => false,
                'load' => array(),
                'map' => null,
                'mount' => array(),
                'mung' => array(),
                'extractable' => false,
                'banner' => null,
                'shebang' => '#!/usr/bin/env php',
            ),
            'compression' => 'NONE',
            'mode' => 644,
            'output' => 'output.phar',
        );

        $stub = array(
            'stub' => array(
                'web' => null,
            )
        );

        $this->assertEquals(
            $expected,
            $this->processor->processConfiguration(
                $this->definition,
                array($stub)
            )
        );
    }

    /**
     * Make sure that the default source settings are set as expected.
     */
    public function testDefaultSource()
    {
        $expected = array(
            'sources' => array(
                'base' => '/path/to/base/directory',
                'dirs' => array(
                    array(
                        'path' => 'path/to/directory',
                        'binary' => false,
                        'extension' => array('php'),
                        'filter' => null,
                        'ignore' => array(),
                        'rename' => null,
                    ),
                    array(
                        'binary' => true,
                        'extension' => array('php'),
                        'filter' => '/filter/',
                        'ignore' => array(
                            'directory/to/ignore/',
                            'file/to/ignore.php',
                        ),
                        'path' => 'path/to/another/directory',
                        'rename' => 'different/internal/path',
                    ),
                ),
                'files' => array(
                    array(
                        'path' => 'path/to/file.php',
                        'binary' => false,
                        'rename' => null,
                    ),
                    array(
                        'binary' => true,
                        'path' => 'path/to/another/file.php',
                        'rename' => 'different/internal/path.php',
                    ),
                ),
            ),
            'compression' => 'NONE',
            'mode' => 644,
            'output' => 'output.phar',
        );

        $sources = array(
            'sources' => array(
                'base' => '/path/to/base/directory',
                'dirs' => array(
                    'path/to/directory',
                    array(
                        'binary' => true,
                        'filter' => '/filter/',
                        'ignore' => array(
                            'directory/to/ignore/',
                            'file/to/ignore.php',
                        ),
                        'path' => 'path/to/another/directory',
                        'rename' => 'different/internal/path',
                    ),
                ),
                'files' => array(
                    'path/to/file.php',
                    array(
                        'binary' => true,
                        'path' => 'path/to/another/file.php',
                        'rename' => 'different/internal/path.php',
                    ),
                ),
            )
        );

        $this->assertEquals(
            $expected,
            $this->processor->processConfiguration(
                $this->definition,
                array($sources)
            )
        );
    }

    /**
     * Make sure that the default stub settings are set as expected.
     */
    public function testDefaultStub()
    {
        $expected = array(
            'stub' => array(
                'banner' => <<<BANNER
This is an example

    banner that span

        multiple lines
BANNER
,
                'extractable' => true,
                'intercept' => true,
                'load' => array(
                    array(
                        'file' => '/path/to/test.phar',
                        'alias' => null,
                    ),
                    array(
                        'alias' => 'alias.phar',
                        'file' => '/path/to/test.phar',
                    ),
                ),
                'map' => 'test.phar',
                'mount' => array(
                    array(
                        'external' => '/path/to/dir',
                        'internal' => 'mount/point'
                    ),
                    array(
                        'external' => '/path/to/file.php',
                        'internal' => 'config/file.php'
                    ),
                ),
                'mung' => array(
                    'PHP_SELF',
                ),
                'require' => array(
                    array(
                        'file' => 'internal/path/to/file.php',
                        'internal' => true,
                    ),
                    array(
                        'file' => '/external/path/to/file.php',
                        'internal' => false,
                    ),
                ),
                'shebang' => '#!/usr/bin/php',
                'source' => array(
                    array(
                        'source' => 'testFunc();',
                        'after' => true,
                    ),
                    array(
                        'after' => false,
                        'source' => 'anotherFunc();',
                    ),
                ),
                'web' => array(
                    'alias' => 'test.phar',
                    'not_found' => '404.php',
                    'rewrite' => 'testRewrite',
                    'index' => 'index.php',
                    'mime' => array(
                        'avi' => 'video/avi',
                        'bmp' => 'image/bmp',
                        'c' => 'text/plain',
                        'c++' => 'text/plain',
                        'cc' => 'text/plain',
                        'cpp' => 'text/plain',
                        'css' => 'text/css',
                        'dtd' => 'text/plain',
                        'gif' => 'image/gif',
                        'h' => 'text/plain',
                        'htm' => 'text/html',
                        'html' => 'text/html',
                        'htmls' => 'text/html',
                        'ico' => 'image/x-ico',
                        'inc' => Phar::PHP,
                        'jpe' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'jpg' => 'image/jpeg',
                        'js' => 'application/x-javascript',
                        'log' => 'text/plain',
                        'mid' => 'audio/midi',
                        'midi' => 'audio/midi',
                        'mod' => 'audio/mod',
                        'mov' => 'movie/quicktime',
                        'mp3' => 'audio/mp3',
                        'mpeg' => 'video/mpeg',
                        'mpg' => 'video/mpeg',
                        'pdf' => 'application/pdf',
                        'php' => Phar::PHP,
                        'phps' => Phar::PHPS,
                        'png' => 'image/png',
                        'rng' => 'text/plain',
                        'swf' => 'application/shockwave-flash',
                        'tif' => 'image/tiff',
                        'tiff' => 'image/tiff',
                        'txt' => 'text/plain',
                        'wav' => 'audio/wav',
                        'xbm' => 'image/xbm',
                        'xml' => 'text/xml',
                        'xsd' => 'text/plain',
                    ),
                ),
            ),
            'compression' => 'NONE',
            'mode' => 644,
            'output' => 'output.phar',
        );

        $stub = array(
            'stub' => array(
                'banner' => <<<BANNER
This is an example

    banner that span

        multiple lines
BANNER
,
                'extractable' => true,
                'intercept' => true,
                'load' => array(
                    array(
                        'file' => '/path/to/test.phar',
                    ),
                    array(
                        'alias' => 'alias.phar',
                        'file' => '/path/to/test.phar',
                    ),
                ),
                'map' => 'test.phar',
                'mount' => array(
                    array(
                        'external' => '/path/to/dir',
                        'internal' => 'mount/point'
                    ),
                    array(
                        'external' => '/path/to/file.php',
                        'internal' => 'config/file.php'
                    ),
                ),
                'mung' => array(
                    'PHP_SELF',
                ),
                'require' => array(
                    array(
                        'file' => 'internal/path/to/file.php',
                    ),
                    array(
                        'file' => '/external/path/to/file.php',
                        'internal' => false,
                    ),
                ),
                'shebang' => '#!/usr/bin/php',
                'source' => array(
                    array(
                        'source' => 'testFunc();',
                    ),
                    array(
                        'after' => false,
                        'source' => 'anotherFunc();',
                    ),
                ),
                'web' => array(
                    'alias' => 'test.phar',
                    'not_found' => '404.php',
                    'rewrite' => 'testRewrite',
                ),
            ),
        );

        $this->assertEquals(
            $expected,
            $this->processor->processConfiguration(
                $this->definition,
                array($stub)
            )
        );
    }

    /**
     * Make sure that nothing is set if no configuration is given.
     */
    public function testEmptyConfig()
    {
        $this->assertEquals(
            array(
                'compression' => 'NONE',
                'mode' => 644,
                'output' => 'output.phar',
            ),
            $this->processor->processConfiguration(
                $this->definition,
                array(
                    array() // empty config
                )
            )
        );
    }

    /**
     * Sets up the definition and processor.
     */
    protected function setUp()
    {
        $this->definition = new Definition();
        $this->processor = new Processor();
    }
}
