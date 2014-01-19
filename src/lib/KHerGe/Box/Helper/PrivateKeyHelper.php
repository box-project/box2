<?php

namespace KHerGe\Box\Helper;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper;

/**
 * Manages private key extraction from PEM formatted files.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class PrivateKeyHelper extends Helper
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'private-key';
    }

    /**
     * Returns a usable private and public key from a PEM encoded string.
     *
     * @param string $key  The private key.
     * @param string $pass The private key passphrase.
     *
     * @return array The private ([0]) and public ([1]) key.
     *
     * @throws InvalidArgumentException If a passphrase is required.
     * @throws RuntimeException         If the file could not be parsed.
     */
    public function parsePem($key, $pass = null)
    {
        $this->clearErrors();

        if (false === ($resource = openssl_pkey_get_private($key, $pass))) {
            $error = openssl_error_string();

            if (preg_match('/(bad password|bad decrypt)/', $error)) {
                throw new InvalidArgumentException(
                    'The private key passphrase is invalid.'
                );
            }

            throw new RuntimeException(
                "The private key could not be parsed: $error"
            );
        }

        openssl_pkey_export($resource, $private);

        $details = openssl_pkey_get_details($resource);

        openssl_pkey_free($resource);

        return array(
            $private,
            $details['key']
        );
    }

    /**
     * Returns a usable private and public key from a PEM encoded file.
     *
     * @param string $file The private key file.
     * @param string $pass The private key passphrase.
     *
     * @return array The private ([0]) and public ([1]) key.
     *
     * @throws InvalidArgumentException If the file path is not valid.
     * @throws RuntimeException         If the file could not be read.
     */
    public function parsePemFile($file, $pass = null)
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(
                "The private key file \"$file\" is not a file or does not exist."
            );
        }

        if (false === ($contents = file_get_contents($file))) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                "The private key file \"$file\" could not be read."
            );
        }
        // @codeCoverageIgnoreEnd

        return $this->parsePem($contents, $pass);
    }

    /**
     * Clears previous OpenSSL errors.
     */
    private function clearErrors()
    {
        while (openssl_error_string()) {
            // continue
        }
    }
}
