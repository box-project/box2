<?php

namespace KevinGH\Box\Command;

use Exception;
use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies the Phar signature.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Verify extends Command
{
    /**
     * @override
     */
    protected function configure()
    {
        $this->setName('verify');
        $this->setDescription('Verifies the Phar signature.');
        $this->setHelp(<<<HELP
The <info>%command.name%</info> command will verify the signature of the Phar.
HELP
        );
        $this->addArgument(
            'phar',
            InputArgument::REQUIRED,
            'The Phar file.'
        );
    }

    /**
     * @override
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verbose = (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity());
        $phar = $input->getArgument('phar');

        if ($verbose) {
            $output->writeln('Verifying the Phar...');
        }

        if (false === is_file($phar)) {
            $output->writeln(sprintf(
                '<error>The path "%s" is not a file or does not exist.</error>',
                $phar
            ));

            return 1;
        }

        try {
            $phar = new Phar($input->getArgument('phar'));

            $output->writeln('<info>The Phar passed verification.</info>');

            if ($verbose) {
                $signature = $phar->getSignature();

                $output->writeln($signature['hash_type'] . ' Signature:');
                $output->writeln($signature['hash']);
            }
        } catch (Exception $exception) {
            $output->writeln('<error>The Phar failed verification.</error>');

            if ($verbose) {
                throw $exception;
            }

            return 1;
        }
    }
}