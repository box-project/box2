<?php

namespace KevinGH\Box\Tests\Command\Key;

use Crypt_RSA;

class MockCryptRSA extends Crypt_RSA
{
    public function getPublicKey($type = CRYPT_RSA_PUBLIC_FORMAT_PKCS1)
    {
        return false;
    }
}
