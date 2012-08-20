<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Command\OpenSsl;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extracts the public key from a private key.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class ExtractPublic extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('openssl:extract-public');
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
            'The output file. (default: private.key)'
        );

        $this->addOption(
            'prompt',
            'p',
            InputOption::VALUE_NONE,
            'Prompt for a passphrase.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getHelper('openssl')->extractPublicKeyToFile(
            $input->getOption('out') ?: 'public.key',
            'file://' . $input->getArgument('private'),
            $input->getOption('prompt') ? $this->getHelper('dialog')->ask($output, 'Passphrase: ') : null
        );

        $box = $this->getHelper('box');

        if ($box->isVerbose($output)) {
            $box->setOutput($output);
            $box->putln('OPENSSL', '<info>Public key extracted!</info>', true);
        }
    }
}

