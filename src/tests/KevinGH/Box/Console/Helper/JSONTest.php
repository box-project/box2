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

    use Exception,
        KevinGH\Box\Test\TestCase;

    class JSONTest extends TestCase
    {
        private $json;

        public function setUp()
        {
            $this->json = new JSON;
        }

        public function testParseFile()
        {
            $file = $this->file(utf8_encode(json_encode(array('rand' => $rand = rand()))));

            $this->assertEquals(array('rand' => $rand), $this->json->parseFile($file));

            $file = $this->file('');

            $this->assertNull($this->json->parseFile($file));
        }

        /**
         * @expectedException InvalidArgumentException
         * @expectedExceptionMessage The file "test.json" does not exist.
         */
        public function testParseFileNotExist()
        {
            $this->json->parseFile('test.json');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage The file
         */
        public function testParseFileReadError()
        {
            $this->json->parseFile('/root');
        }

        /**
         * @expectedException Seld\JsonLint\ParsingException
         * @expectedExceptionMessage The file
         */
        public function testParseFileInvalid()
        {
            $file = $this->file('{');

            $this->json->parseFile($file);
        }

        public function testValidate()
        {
            $data = array(
                'algorithm' => 'SHA1',
                'alias' => 'default.phar',
                'base-path' => '/test/path',
                'blacklist' => array(
                    'path/to/file1',
                    'path/to/file2'
                ),
                'directories' => '/path/to/dir',
                'directories-bin' => '/path/to/dir',
                'files' => '/path/to/file',
                'files-bin' => '/path/to/file',
                'finder' => array(
                    array(
                        'name' => array('*.php', '*.phtml'),
                        'in' => array('/path/to/dir', '/path/to/another/dir')
                    ),
                    array(
                        'name' => '*.php',
                        'in' => '/path/to/yet/another/dir'
                    )
                ),
                'finder-bin' => array(
                    array(
                        'name' => array('*.php', '*.phtml'),
                        'in' => array('/path/to/dir', '/path/to/another/dir')
                    ),
                    array(
                        'name' => '*.php',
                        'in' => '/path/to/yet/another/dir'
                    )
                ),
                'git-version' => 'test-var',
                'intercept' => true,
                'key' => '/path/to/key',
                'key-pass' => true,
                'main' => '/path/to/main',
                'metadata' => array(
                    'rand' => array()
                ),
                'output' => 'default.phar',
                'replacements' => array(
                    'rand' => rand()
                ),
                'stub' => true
            );

            $this->assertNull($this->json->validate('test.json', $data));
        }

        /**
         * @expectedException KevinGH\Box\Console\Exception\JSONValidationException
         * @expectedExceptionMessage The file "test.json" is not valid.
         */
        public function testValidateInvalid()
        {
            $this->json->validate('test.json', array('rand' => rand()));
        }

        public function testValidateSyntax()
        {
            $this->assertNull($this->json->validateSyntax('test.json', '{}'));
        }

        /**
         * @expectedException KevinGH\Box\Console\Exception\JSONValidationException
         * @expectedExceptionMessage The file "test.json" is not valid UTF-8.
         */
        public function testValidateSyntaxInvalidUTF8()
        {
            if (false === extension_loaded('runkit'))
            {
                $this->markTestSkipped('The "runkit" extension is not available.');

                return;
            }

            runkit_method_rename('Seld\JsonLint\JsonParser', 'lint', '_lint');
            runkit_method_add('Seld\JsonLint\JsonParser', 'lint', '', 'return null;');

            $this->redefine('json_last_error', '', 'return JSON_ERROR_UTF8;');

            try
            {
                $this->json->validateSyntax('test.json', '{}');
            }

            catch (Exception $exception)
            {
            }

            $this->restore('json_last_error');

            runkit_method_remove('Seld\JsonLint\JsonParser', 'lint');
            runkit_method_rename('Seld\JsonLint\JsonParser', '_lint', 'lint');

            if (isset($exception))
            {
                throw $exception;
            }
        }

        /**
         * @expectedException Seld\JsonLint\ParsingException
         * @expectedExceptionMessage The file "test.json" does not contain valid JSON data:
         */
        public function testValidateSyntaxInvalid()
        {
            $this->json->validateSyntax('test.json', '{');
        }
    }