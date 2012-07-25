<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console\Command;

    use KevinGH\Box\Test\CommandTestCase,
        KevinGH\Box\Test\Dialog,
        Phar,
        Symfony\Component\Console\Output\OutputInterface;

    class InfoTest extends CommandTestCase
    {
        const COMMAND = 'info';

        public function testExecute()
        {
            $this->tester->execute(array('command' => self::COMMAND));

            $expected = 'PHAR v' . Phar::apiVersion() . "\n\n";
            $expected .= "Compression algorithms:\n";

            foreach (Phar::getSupportedCompression() as $algorithm)
            {
                $expected .= "    - $algorithm\n";
            }

            $expected .= "\nSignature algorithms:\n";

            foreach (Phar::getSupportedSignatures() as $algorithm)
            {
                $expected .= "    - $algorithm\n";
            }

            $this->assertEquals($expected, $this->tester->getDisplay());
        }

        public function testExecuteWithPhars()
        {
            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => array(
                    $path = $this->resource('test.phar', true),
                    '/does/not/exist'
                )
            ));

            $expected = $path . ":\n";
            $expected .= "    - API v1.1.0\n";
            $expected .= "    - Compression: none\n";
            $expected .= "    - Metadata: No\n";
            $expected .= "    - Signature: SHA-1\n\n";
            $expected .= "/does/not/exist: does not exist\n\n";

            $this->assertEquals($expected, $this->tester->getDisplay());
        }

        public function testExecuteWithCorruptPhars()
        {
            $temp = $this->file($this->resource('test.phar'));

            $this->tester->execute(array(
                'command' => self::COMMAND,
                'phar' => array($temp)
            ));

            $this->assertEquals(
                "$temp:\n    - Is corrupt.\n\n",
                $this->tester->getDisplay()
            );
        }

        /**
         * @dataProvider getPharCompressAlgos
         */
        public function testGetCompressionName($code, $name)
        {
            $method = $this->method($this->command, 'getCompressionName');

            $this->assertEquals($name, $method($code));
        }

        public function getPharCompressAlgos()
        {
            $algos = array(
                array(9999, 'unrecognized')
            );

            if (defined('Phar::BZ2')) $algos[] = array(Phar::BZ2, 'BZ2');
            if (defined('Phar::GZ')) $algos[] = array(Phar::GZ, 'GZ');
            if (defined('Phar::TAR')) $algos[] = array(Phar::TAR, 'TAR');
            if (defined('Phar::ZIP')) $algos[] = array(Phar::ZIP, 'ZIP');

            return $algos;
        }
    }