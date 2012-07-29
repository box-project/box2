<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Command\Key;

use InvalidArgument;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extracts a public key from a private key.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Extract extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('key:extract')
             ->setDescription('Extracts the public key from the private key.');

        $this->addOption(
            'in',
            'i',
            InputOption::VALUE_REQUIRED,
            'The input private key file. (default: private.key)'
        );

        $this->addOption(
            'out',
            'o',
            InputOption::VALUE_REQUIRED,
            'The output file. (default: public.key)'
        );

        $this->addOption(
            'passphrase',
            'p',
            InputOption::VALUE_REQUIRED,
            'The passphrased used instead of prompting.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $in = $input->getOption('in') ?: 'private.key';

        $out = $input->getOption('out') ?: 'public.key';

        if (false === file_exists($in)) {
            $output->writeln(sprintf(
                '<error>The private key file "%s" does not exist.</error>',
                $in
            ));

            return 1;
        }

        if ('' == ($passphrase = $input->getOption('passphrase'))) {
            $dialog = $this->getHelper('dialog');

            $passphrase = $dialog->ask($output, 'Passphrase (blank for none): ');
        }

        $this->getHelper('openssl')->createPublicFileFromFile(
            realpath($in),
            $out,
            $passphrase
        );
    }
}