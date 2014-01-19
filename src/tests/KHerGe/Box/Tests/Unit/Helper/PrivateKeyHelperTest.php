<?php

namespace KHerGe\Box\Tests\Unit\Helper;

use KHerGe\Box\Helper\PrivateKeyHelper;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Performs unit testing on `PrivateKeyHelper`
 *
 * @see KHerGe\Box\Helper\PrivateKeyHelper
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class PrivateKeyHelperTest extends TestCase
{
    /**
     * The private key helper instance being tested.
     *
     * @var PrivateKeyHelper
     */
    private $helper;

    /**
     * The temporary file manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * Make sure that the expected helper name is returned.
     */
    public function testGetName()
    {
        $this->assertEquals(
            'private-key',
            $this->helper->getName(),
            'The expected helper name should be returned.'
        );
    }

    /**
     * Make sure that we can parse a PEM encoded string.
     */
    public function testParsePem()
    {
        $expected = $this->createKey();

        $this->assertEquals(
            $expected,
            $this->helper->parsePem($expected[0]),
            'The private and public key should be returned.'
        );

        $expected = $this->createKey('test');
        $result = $this->helper->parsePem($expected[0], 'test');

        $this->assertNotRegExp(
            '/ENCRYPTED/i',
            $result[0],
            'The returned private key should not be encrypted.'
        );

        $this->assertEquals(
            $expected[1],
            $result[1],
            'THe public key should be returned.'
        );
    }

    /**
     * Make sure that we can parse a PEM encoded file.
     */
    public function testParsePemFile()
    {
        $expected = $this->createKey();
        $file = $this->temp->createFile();

        file_put_contents($file, $expected[0]);

        $this->assertEquals(
            $expected,
            $this->helper->parsePemFile($file),
            'The private and public key should be returned.'
        );
    }

    /**
     * Make sure that an invalid file path throws an exception.
     */
    public function testParsePemFileInvalidPath()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The private key file "/does/not/exist" is not a file or does not exist.'
        );

        $this->helper->parsePemFile('/does/not/exist');
    }

    /**
     * Make sure that an invalid key file throws an exception.
     */
    public function testParsePemInvalidKey()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The private key could not be parsed:'
        );

        $this->helper->parsePem('test');
    }

    /**
     * Make sure that an invalid passphrase throws an exception.
     */
    public function testParsePemInvalidPassphrase()
    {
        $key = $this->createKey('test');

        $this->setExpectedException(
            'InvalidArgumentException',
            'The private key passphrase is invalid.'
        );

        $this->helper->parsePem($key[0], 'invalid');
    }

    /**
     * @override
     */
    protected function setUp()
    {
        $this->helper = new PrivateKeyHelper();
        $this->temp = new Temp();

        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('The "openssl" extension is required.');
        }
    }

    /**
     * @override
     */
    protected function tearDown()
    {
        $this->temp->purgePaths();
    }

    /**
     * Generates a new private key.
     *
     * @param string $pass The passphrase.
     *
     * @return array The private and public key.
     */
    private function createKey($pass = null)
    {
        $resource = openssl_pkey_new(
            array(
                'private_key_bits' => 512,
                'private_key_type' => OPENSSL_KEYTYPE_RSA
            )
        );

        openssl_pkey_export($resource, $key, $pass);

        $details = openssl_pkey_get_details($resource);

        openssl_pkey_free($resource);

        return array(
            $key,
            $details['key']
        );
    }
}
