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

use KevinGH\Box\Box;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adds or replaces a file in an existing PHAR.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Add extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('add');
        $this->setDescription('Add or replace a file in an existing PHAR.');

        $this->addOption(
            'bin',
            'b',
            InputOption::VALUE_NONE,
            'Treat the file as binary.'
        );

        $this->addOption(
            'configuration',
            'c',
            InputOption::VALUE_REQUIRED,
            'The alternative configuration file path.'
        );

        $this->addOption(
            'main',
            'm',
            InputOption::VALUE_NONE,
            'Treat the file as the main script.'
        );

        $this->addOption(
            'replace',
            'r',
            InputOption::VALUE_NONE,
            'Allow replacement.'
        );

        $this->addOption(
            'stub',
            's',
            InputOption::VALUE_NONE,
            'Treat the file as a stub.'
        );

        $this->addArgument(
            'phar',
            InputArgument::REQUIRED,
            'The PHAR to modify.'
        );

        $this->addArgument(
            'external',
            InputArgument::REQUIRED,
            'The path to the file to add or replace.'
        );

        $this->addArgument(
            'internal',
            InputArgument::OPTIONAL,
            'The path inside the PHAR.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $box = $this->getHelper('box');
        $config = $box->find($input->getOption('configuration'));
        $phar = new Box($input->getArgument('phar'));
        $external = $input->getArgument('external');

        $phar->setReplacements($config['replacements']);

        if ($input->getOption('stub')) {
            $phar->setStubFile($external);
        } else {
            if (null === ($internal = $input->getArgument('internal'))) {
                $output->writeln('<error>The internal path is required.</error>');

                return 1;
            }

            if (isset($phar[$internal]) && (false === $input->getOption('replace'))) {
                $output->writeln(sprintf(
                    '<error>The path "%s" already exists in the PHAR</error>',
                    $internal
                ));

                return 1;
            }

            if ($input->getOption('bin')) {
                $phar->addFile($external, $internal);
            } else {
                $phar->importFile($external, $internal, $input->getOption('main'));
            }
        }

        if ($box->isVerbose($output)) {
            $box->setOutput($output);
            $box->putln('ADD', sprintf(
                '<info>Successfully %s!</info>',
                $input->getOption('replace') ? 'replaced' : 'added'
            ));
        }
    }
}

