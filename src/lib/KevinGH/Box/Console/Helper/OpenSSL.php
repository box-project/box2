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

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper;

/**
 * Manages the creation of private and public keys.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class OpenSSL extends Helper
{
    /**
     * The supported algorithms.
     *
     * @type array
     */
    private $algorithms = array(
        'dh' => OPENSSL_KEYTYPE_DH,
        'dsa' => OPENSSL_KEYTYPE_DSA,
        'rsa' => OPENSSL_KEYTYPE_RSA
    );

    /**
     * Extracts the public key from the private key string.
     *
     * @param string      $private    The private key.
     * @param null|string $passphrase The passphrase.
     *
     * @return string The public key.
     *
     * @throws RuntimeException If the key could not be extracted.
     */
    public function createPublic($private, $passphrase = null)
    {
        while (openssl_error_string()) {
            // clear out buffered messages
        }

        if (false === ($resource = openssl_pkey_get_private($private, $passphrase))) {
            throw new RuntimeException(sprintf(
                'The private key could not be processed: %s',
                openssl_error_string()
            ));
        }

        if (false === ($details = openssl_pkey_get_details($resource))) {
            throw new RuntimeException(sprintf(
                'The details of the private key could not be extracted: %s',
                openssl_error_string()
            ));
        }

        openssl_free_key($resource);

        return $details['key'];
    }

    /**
     * Extracts the public key from the private string and saves it to a file.
     *
     * @param string      $file       The public key file.
     * @param string      $private    The private key.
     * @param null|string $passphrase The passphrase.
     *
     * @throws RuntimeException If the file could not be written.
     */
    public function createPublicFile($file, $private, $passphrase = null)
    {
        $public = $this->createPublic($private, $passphrase);

        if (false === @ file_put_contents($file, $public)) {
            $error = error_get_last();

            throw new RuntimeException(sprintf(
                'The public key file "%s" could not be written: %s',
                $file,
                $error['message']
            ));
        }
    }

    /**
     * Creates a new private key and saves it to a file.
     *
     * @param string      $in         The private key file.
     * @param string      $out        The public key file.
     * @param null|string $passphrase The passphrase.
     *
     * @throws RuntimeException If the file could not be written.
     */
    public function createPublicFileFromFile($in, $out, $passphrase = null)
    {
        $key = $this->createPublicFile($out, "file://$in", $passphrase);
    }

    /**
     * Creates a private key.
     *
     * @param null|string  $passphrase The passphrase.
     * @param null|string  $type       The key type.
     * @param null|integer $bits       The number of bits.
     *
     * @return string The private key string.
     *
     * @throws InvalidArgumentException If the key type is invalid.
     * @throws RuntimeException If the key could not be created.
     */
    public function createPrivate($passphrase = null, $type = null, $bits = null)
    {
        $config = array();

        if ($type) {
            if (isset($this->algorithms[$type])) {
                $type = $this->algorithms[$type];
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Invalid key type: %s',
                    $type
                ));
            }

            $config['private_key_type'] = $type;
        }

        if ($bits) {
            $config['private_key_bits'] = $bits;
        }

        while (openssl_error_string()) {
            // clear out buffered messages
        }

        if (false === ($resource = openssl_pkey_new($config))) {
            throw new RuntimeException(sprintf(
                'The private key could not be created: %s',
                openssl_error_string()
            ));
        }

        if (false === openssl_pkey_export($resource, $key, $passphrase)) {
            throw new RuntimeException(sprintf(
                'The details of the private key could not be retrieved: %s',
                openssl_error_string()
            ));
        }

        openssl_free_key($resource);

        return $key;
    }

    /**
     * Creates a private key and saves it to a file.
     *
     * @param string       $file       The file path.
     * @param null|string  $passphrase The passphrase.
     * @param null|string  $type       The key type.
     * @param null|integer $bits       The number of bits.
     *
     * @return string The private key string.
     *
     * @throws RuntimeException If the file could not be written.
     */
    public function createPrivateFile(
        $file,
        $passphrase = null,
        $type = null,
        $bits = null
    )
    {
        $key = $this->createPrivate($passphrase, $type, $bits);

        if (false === @ file_put_contents($file, $key)) {
            $error = error_get_last();

            throw new RuntimeException(sprintf(
                'The private key file "%s" could not be written: %s',
                $file,
                $error['message']
            ));
        }
    }

    /**
     * Returns the supported key types.
     *
     * @return array The key types.
     */
    public function getKeyTypes()
    {
        return $this->algorithms;
    }

    /** {@inheritDoc} */
    public function getName()
    {
        return 'openssl';
    }
}