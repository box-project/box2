<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box;

use Exception;
use KevinGH\Box\Box;
use KevinGH\Box\Test\TestCase;
use KevinGH\Elf\OpenSsl;

class BoxTest extends TestCase
{
    /** @var Application */
    private $box;
    private $boxAlias;
    private $boxName;

    protected function setUp()
    {
        parent::setUp();

        unlink($this->boxName = tempnam($this->currentDir, 'box'));

        $this->boxName .= '.phar';
        $this->boxAlias = basename($this->boxName);
        $this->box = new Box($this->boxName, 0, $this->boxAlias);
    }

    protected function tearDown()
    {
        unset($this->box);

        parent::tearDown();
    }

    public function testConstructor()
    {
        $alias = $this->property($this->box, 'alias');
        $name = $this->property($this->box, 'name');

        $this->assertEquals($this->boxAlias, $alias());
        $this->assertEquals($this->boxName, $name());
    }

    public function testCompactSource()
    {
        $compactor = $this->property($this->box, 'compactor');

        $compactor(function ($source) {
            return str_replace('TestClass', 'ReplacedClass', $source);
        });

        $this->assertEquals(
            $this->getResource('tests/class-compacted.php', true),
            $this->box->compactSource($this->getResource('tests/class-original.php', true))
        );
    }

    public function testCreateStub()
    {
        $this->box->addFile($this->getResource('tests/main-original.php'), 'main.php');

        $intercept = $this->property($this->box, 'intercept');
        $main = $this->property($this->box, 'main');
        $stub = $this->getResource('tests/stub-generated.php', true);
        $stub = str_replace('$alias', $this->boxAlias, $stub);
        $stub = str_replace('$main', 'main.php', $stub);

        $intercept(true);
        $main('main.php');

        $this->assertEquals(
            $stub,
            $this->box->createStub()
        );
    }

    public function testDoReplacements()
    {
        $replacements = $this->property($this->box, 'replacements');

        $replacements(array('name' => 'world'));

        $this->assertEquals(
            $this->getResource('tests/main-replaced.php', true),
            $this->box->doReplacements($this->getResource('tests/main-original.php', true))
        );
    }

    public function testImportFile()
    {
        $this->box->importFile('main.php', $this->getResource('tests/main-original.php'), true);

        $main = $this->property($this->box, 'main');

        $this->assertEquals('main.php', $main());
    }

    public function testImportFileNotExist()
    {
        unlink($file = $this->file());

        $this->setExpectedException(
            'InvalidArgumentException',
            "The path \"$file\" is not a file or it does not exist."
        );

        $this->box->importFile('file.php', $file);
    }

    public function testImportFileReadError()
    {
        $file = $this->file();

        if ($this->redeclare($this, 'file_get_contents', '', 'return false;')) {
            return;
        }

        $this->setExpectedException(
            'RuntimeException',
            "The file \"$file\" could not be read:"
        );

        $this->box->importFile('file.php', $file);
    }

    public function testImportSource()
    {
        $compactor = $this->property($this->box, 'compactor');
        $replacements = $this->property($this->box, 'replacements');
        $main = $this->property($this->box, 'main');

        $replacements(array('name' => 'world'));
        $compactor(function ($source) {
            return str_replace('Hello', 'Goodbye', $source);
        });

        $this->box->importSource('main.php', $this->getResource('tests/main-original.php', true), true);

        $this->assertEquals('main.php', $main());
        $this->assertEquals(
            $this->getResource('tests/main-compacted.php', true),
            $this->box['main.php']->getContent()
        );
    }

    public function testSetCompactor()
    {
        $compactor = $this->property($this->box, 'compactor');

        $this->assertNull($compactor());

        $callback = function () {
        };

        $this->box->setCompactor($callback);

        $this->assertSame($callback, $compactor());
    }

    public function testSetIntercept()
    {
        $intercept = $this->property($this->box, 'intercept');

        $this->assertFalse($intercept());

        $this->box->setIntercept(true);

        $this->assertTrue($intercept());
    }

    public function testSetReplacements()
    {
        $replacements = $this->property($this->box, 'replacements');

        $this->assertSame(array(), $replacements());

        $values = array('rand' => rand());

        $this->box->setReplacements($values);

        $this->assertEquals($values, $replacements());
    }

    public function testSetStubFile()
    {
        $this->box->setReplacements(array('name' => 'world'));
        $this->box->setStubFile($this->getResource('tests/stub-custom.php'), true);

        $this->assertEquals(
            $this->getResource('tests/stub-replaced.php', true) . ' ?>',
            trim($this->box->getStub())
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The path "stub.php" is not a file or it does not exist.
     */
    public function testSetStubFileNotExist()
    {
        $this->box->setStubFile('stub.php');
    }

    public function testSetStubFileReadError()
    {
        $file = $this->file();

        if ($this->redeclare($this, 'file_get_contents', '', 'return false;')) {
            return;
        }

        $this->setExpectedException(
            'RuntimeException',
            "The stub file \"$file\" could not be read:"
        );

        $this->box->setStubFile($file);
    }

    public function testUsePrivateKeyFile()
    {
        if ($this->checkSupport($this, 'openssl')) {
            return;
        }

        $openssl = new OpenSsl();

        $openssl->createPrivateKeyFile('private.key', 'phpunit');

        $this->box->importFile('main.php', $this->getResource('tests/main-original.php'), true);
        $this->box->setStub($this->box->createStub());
        $this->box->usePrivateKeyFile('private.key', 'phpunit');

        unset($this->box);

        $this->assertFileExists($this->boxName . '.pubkey');
        $this->assertEquals(
            $openssl->extractPublicKey(file_get_contents('private.key'), 'phpunit'),
            file_get_contents($this->boxName . '.pubkey')
        );

        $this->assertEquals(
            'Hello, @name@!',
            $this->command('php ' . escapeshellarg($this->boxName))
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The path "private.key" is not a file or it does not exist.
     */
    public function testUsePrivateKeyFileNotExist()
    {
        if ($this->checkSupport($this, 'openssl')) {
            return;
        }

        $this->box->usePrivateKeyFile('private.key');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The "openssl" extension is not available.
     */
    public function testUsePrivateKeyFileNoOpenSsl()
    {
        if ($this->checkSupport($this, 'openssl')) {
            return;
        }

        if ($this->redeclare($this, 'extension_loaded', '', 'return false;')) {
            return;
        }

        $this->box->usePrivateKeyFile($this->file());
    }

    public function testUsePrivateKeyFileReadError()
    {
        if ($this->checkSupport($this, 'openssl')) {
            return;
        }

        $file = $this->file();

        if ($this->redeclare($this, 'file_get_contents', '', 'return false;')) {
            return;
        }

        $this->setExpectedException(
            'RuntimeException',
            "The private key file \"$file\" could not be read:"
        );

        $this->box->usePrivateKeyFile($file);
    }

    public function testUsePrivateKeyFileParseError()
    {
        if ($this->checkSupport($this, 'openssl')) {
            return;
        }

        $openssl = new OpenSsl();

        $file = $this->file($openssl->createPrivateKey('phpunit'));

        $this->setExpectedException(
            'RuntimeException',
            "The private key file \"$file\" could not be parsed:"
        );

        $this->box->usePrivateKeyFile($file);
    }

    public function testUsePrivateKeyFileExportError()
    {
        if ($this->checkSupport($this, 'openssl')) {
            return;
        }

        $openssl = new OpenSsl();

        $file = $this->file($openssl->createPrivateKey('phpunit'));

        if ($this->redeclare($this, 'openssl_pkey_export', '$a, &$b', 'return false;')) {
            return;
        }

        $this->setExpectedException(
            'RuntimeException',
            "The private key file \"$file\" could not be exported:"
        );

        $this->box->usePrivateKeyFile($file, 'phpunit');
    }

    public function testUsePrivateKeyFileGetDetailsError()
    {
        if ($this->checkSupport($this, 'openssl')) {
            return;
        }

        $openssl = new OpenSsl();

        $file = $this->file($openssl->createPrivateKey('phpunit'));

        if ($this->redeclare($this, 'openssl_pkey_get_details', '', 'return false;')) {
            return;
        }

        $this->setExpectedException(
            'RuntimeException',
            "The details of the private key file \"$file\" could not be retrieved:"
        );

        $this->box->usePrivateKeyFile($file, 'phpunit');
    }

    public function testUsePrivateKeyFileWriteError()
    {
        if ($this->checkSupport($this, 'openssl')) {
            return;
        }

        $openssl = new OpenSsl();

        $file = $this->file($openssl->createPrivateKey('phpunit'));

        if ($this->redeclare($this, 'file_put_contents', '', 'return false;')) {
            return;
        }

        $this->setExpectedException(
            'RuntimeException',
            "The public key file \"{$this->boxName}.pubkey\" could not be written:"
        );

        $this->box->usePrivateKeyFile($file, 'phpunit');
    }
}

