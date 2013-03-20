<?php

namespace KevinGH\Box\Tests\Command;

use KevinGH\Box\Command\Verify;
use KevinGH\Box\Test\CommandTestCase;
use Phar;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

class VerifyTest extends CommandTestCase
{
    public function testExecute()
    {
        file_put_contents('test.php', '<?php echo "Hello!";');

        $phar = new Phar('test.phar');
        $phar->addFile('test.php', 'test.php');

        $signature = $phar->getSignature();

        unset($phar);

        $tester = $this->getTester();
        $tester->execute(array(
            'command' => 'verify',
            'phar' => 'test.phar'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            <<<OUTPUT
Verifying the Phar...
The Phar passed verification.
{$signature['hash_type']} Signature:
{$signature['hash']}

OUTPUT
            ,
            $this->getOutput($tester)
        );
    }

    public function testExecuteNotExist()
    {

        $tester = $this->getTester();
        $tester->execute(array(
                'command' => 'verify',
                'phar' => 'test.phar'
            ));

        $this->assertEquals(
            <<<OUTPUT
The path "test.phar" is not a file or does not exist.

OUTPUT
            ,
            $this->getOutput($tester)
        );
    }

    public function testExecuteFailed()
    {
        file_put_contents('test.phar', 'bad');

        $tester = $this->getTester();
        $tester->execute(array(
            'command' => 'verify',
            'phar' => 'test.phar'
        ));

        $this->assertEquals(
            <<<OUTPUT
The Phar failed verification.

OUTPUT
            ,
            $this->getOutput($tester)
        );
    }

    public function testExecuteFailedVerbose()
    {
        file_put_contents('test.phar', 'bad');

        $tester = $this->getTester();

        try {
            $tester->execute(array(
                'command' => 'verify',
                'phar' => 'test.phar'
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        } catch (UnexpectedValueException $exception) {
        }

        $this->assertTrue(isset($exception));
        $this->assertEquals(
            <<<OUTPUT
Verifying the Phar...
The Phar failed verification.

OUTPUT
            ,
            $this->getOutput($tester)
        );
    }

    protected function getCommand()
    {
        return new Verify();
    }
}