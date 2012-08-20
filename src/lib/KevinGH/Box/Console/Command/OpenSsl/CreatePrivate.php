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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates a new private key.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class CreatePrivate extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('openssl:create-private');
        $this->setDescription('Creates a new private key.');

        $this->addOption(
            'bits',
            'b',
            InputOption::VALUE_REQUIRED,
            'The number of bits to generate (default: 1024)'
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

        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            'The private key type. (default: rsa)'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getHelper('openssl')->createPrivateKeyFile(
            $input->getOption('out') ?: 'private.key',
            $input->getOption('prompt') ? $this->getHelper('dialog')->ask($output, 'Passphrase: ') : null,
            $input->getOption('type'),
            $input->getOption('bits') ? (int) $input->getOption('bits') : null
        );

        $box = $this->getHelper('box');

        if ($box->isVerbose($output)) {
            $box->setOutput($output);
            $box->putln('OPENSSL', '<info>Private key created!</info>', true);
        }
    }
}

