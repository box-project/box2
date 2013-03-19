<?php

namespace KevinGH\Box\Command\Key;

use KevinGH\Box\Helper\PhpSecLibHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extracts the public key.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Extract extends Command
{
    /**
     * @override
     */
    protected function configure()
    {
        $this->setName('key:extract');
        $this->setDescription('Extracts the public key from a private key.');
        $this->addArgument(
            'private',
            InputArgument::REQUIRED,
            'The private key file.'
        );
        $this->addOption(
            'out',
            'o',
            InputOption::VALUE_REQUIRED,
            'The output file. (default: public.key)',
            'public.key'
        );
        $this->addOption(
            'prompt',
            'p',
            InputOption::VALUE_NONE
        );
    }

    /**
     * @override
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $lib PhpSecLibHelper */
        $lib = $this->getHelper('phpseclib');
        $rsa = $lib->CryptRSA();
        $verbose = (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity());

        if ($verbose) {
            $output->writeln('Extracting public key...');
        }

        if ($input->getOption('prompt')) {
            /** @var $dialog DialogHelper */
            $dialog = $this->getHelper('dialog');

            $rsa->setPassword($dialog->askHiddenResponse(
                $output,
                'Private key passphrase: '
            ));
        }

        if (false === $rsa->loadKey(
            file_get_contents($input->getArgument('private')),
            CRYPT_RSA_PRIVATE_FORMAT_PKCS1
        )){
            $output->writeln(
                '<error>The private key could not be parsed.</error>'
            );

            return 1;
        }

        $rsa->setPublicKey();

        if (false === ($public = $rsa->getPublicKey())) {
            $output->writeln(
                '<error>The public key could not be retrieved.</error>'
            );

            return 1;
        }

        if ($verbose) {
            $output->writeln('Writing public key...');
        }

        file_put_contents($input->getOption('out'), $public);
    }
}