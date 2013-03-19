<?php

namespace KevinGH\Box\Command\Key;

use KevinGH\Box\Helper\PhpSecLibHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates a private key.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Create extends Command
{
    /**
     * @override
     */
    protected function configure()
    {
        $this->setName('key:create');
        $this->setDescription('Creates a new private key.');
        $this->addOption(
            'bits',
            'b',
            InputOption::VALUE_REQUIRED,
            'The number of bits to generate. (default: 1024)',
            1024
        );
        $this->addOption(
            'out',
            'o',
            InputOption::VALUE_REQUIRED,
            'The output file. (default: private.key)',
            'private.key'
        );
        $this->addOption(
            'public',
            null,
            InputOption::VALUE_REQUIRED,
            'The public key output file.'
        );
        $this->addOption(
            'prompt',
            'p',
            InputOption::VALUE_NONE,
            'Prompt for a passphrase.'
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
            $output->writeln(sprintf(
                'Generating %d bit private key...',
                $input->getOption('bits')
            ));
        }

        if ($input->getOption('prompt')) {
            /** @var $dialog DialogHelper */
            $dialog = $this->getHelper('dialog');

            $rsa->setPassword($dialog->askHiddenResponse(
                $output,
                'Private key passphrase: '
            ));
        }

        $rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
        $rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);

        $key = $rsa->createKey($input->getOption('bits'));

        if ($verbose) {
            $output->writeln('Writing private key...');
        }

        file_put_contents($input->getOption('out'), $key['privatekey']);

        if (null !== ($public = $input->getOption('public'))) {
            if ($verbose) {
                $output->writeln('Writing public key...');
            }

            file_put_contents($public, $key['publickey']);
        }
    }
}