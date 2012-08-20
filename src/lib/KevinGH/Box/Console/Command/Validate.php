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

use Exception;
use KevinGH\Box\Box;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Validates the configuration file.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Validate extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('validate');
        $this->setDescription('Validates a configuration file.');

        $this->addArgument(
            'configuration',
            InputArgument::OPTIONAL,
            'The configuration file.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $box = $this->getHelper('box');

        try {
            $box->find($input->getArgument('configuration'));
        } catch (Exception $exception) {
            $output->writeln('<error>The configuration file is invalid.</error>');

            if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity()) {
                throw $exception;
            }
        }

        if (false === isset($exception)) {
            $output->writeln('<info>The configuration file is valid.');
        }
    }
}

