<?php

namespace KevinGH\Box\Tests\Command\Key;

use KevinGH\Box\Command\Key\Extract;
use KevinGH\Box\Test\CommandTestCase;
use KevinGH\Box\Test\FixedResponse;

class ExtractTest extends CommandTestCase
{
    public function testExecute()
    {
        file_put_contents('test.key', <<<KEY
-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,FCBE82562DA52F8D

uJDjwVSvrhznj/eCq8+J7jQLAoqiYYVPbiRMGdZ8bz+nK6p1vCNvySXlGBgaVZ+9
OzDkE7eEbZaIkwtI7gdAeRTmFpa/7xVfJdK85HFC9+ei2QDxCYFFl4Zx7/m6Ymc0
zYGQhiOoQkt1GRjqWxvWC377h7PEz1Rh+GXxNzyRb5fteRGqrZHzp2kL36LvW5Ou
ILBxr5lwCHFKDY786W3ni77D8bNv0NiVKo0ljbKn/L3st+8erQRIaJ+bUobYIcmB
erqhP0vhufhAcJg0nKbQvtkY5GYmuof/MV6yN3Czqdoga5jjvl7PegOUvDJ3YbNB
sVfvUmDCRaojchJP8Cp/KcvkcEul2U4158QPr4opEEzemFqy5i9VYEGpDIZlPWjZ
AzcVp7Y/MqjdQLiSRYu6fsQvAEAauJD9wETXLWgYfSw=
-----END RSA PRIVATE KEY-----
KEY
        );

        $this->app->getHelperSet()->set(new FixedResponse('test'));

        $tester = $this->getTester();
        $tester->execute(array(
            'command' => 'key:extract',
            'private' => 'test.key',
            '--out' => 'test.pub',
            '--prompt' => true
        ));

        $this->assertRegExp('/PUBLIC KEY/', file_get_contents('test.pub'));
    }

    protected function getCommand()
    {
        return new Extract();
    }
}