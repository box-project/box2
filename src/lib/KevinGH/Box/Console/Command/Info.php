<?php

/* This file is part of Bo.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Command;

use InvalidArgumentException;
use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

/**
 * The command that displays PHAR information.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Info extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('info')
             ->setDescription('Display information about the PHAR extension or file.');

        $this->addArgument(
            'phar',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'The PHAR file name.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($phars = $input->getArgument('phar')) {
            foreach ($phars as $phar) {
                if (false === file_exists($phar)) {
                    $output->writeln("<error>$phar: does not exist</error>\n");

                    continue;
                } else {
                    $output->writeln("$phar:");
                }

                try {
                    $object = new Phar($phar);
                } catch (UnexpectedValueException $exception) {
                    $output->writeln("    - <error>Is corrupt.</error>\n");

                    continue;
                }

                $output->writeln("    - API v" . $object->getVersion());

                $output->writeln(sprintf(
                    '    - Compression: %s',
                    $this->getCompressionName($object->isCompressed())
                ));

                $output->writeln("    - Metadata: " . ($object->hasMetadata() ? 'Yes' : 'No'));

                $signature = $object->getSignature();

                $output->writeln("    - Signature: {$signature['hash_type']}");

                unset($object);

                $output->writeln('');
            }
        } else {
            $output->writeln('PHAR v' . Phar::apiVersion() . "\n");

            $output->writeln('Compression algorithms:');

            foreach (Phar::getSupportedCompression() as $algorithm) {
                $output->writeln("    - $algorithm");
            }

            $output->writeln("\nSignature algorithms:");

            foreach (Phar::getSupportedSignatures() as $algorithm) {
                $output->writeln("    - $algorithm");
            }
        }
    }

    /**
     * Returns the name of the compression algorithm, if any.
     *
     * @param integer $code The algorithm code.
     *
     * @return string The algorithm name.
     */
    protected function getCompressionName($code)
    {
        if (false === $code) {
            return 'none';
        }

        switch ($code) {
            case Phar::BZ2:
                return 'BZ2';
            case Phar::GZ:
                return 'GZ';
            case Phar::TAR:
                return 'TAR';
            case Phar::ZIP:
                return 'ZIP';
        }

        return 'unrecognized';
    }
}