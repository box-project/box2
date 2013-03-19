<?php

namespace KevinGH\Box\Helper;

use Crypt_RSA;
use Symfony\Component\Console\Helper\Helper;

/**
 * Force use of internal generator.
 *
 * @var integer
 */
define('CRYPT_RSA_MODE', 1);

/**
 * A phpseclib helper.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class PhpSecLibHelper extends Helper
{
    /**
     * Returns a new instance of Crypt_RSA.
     *
     * @return Crypt_RSA The instance.
     */
    public function CryptRSA()
    {
        return new Crypt_RSA();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'phpseclib';
    }
}