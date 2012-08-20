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
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

/**
 * Verifies the PHAR.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Verify extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('verify');
        $this->setDescription('Verifies a PHAR.');

        $this->addArgument(
            'phar',
            InputArgument::REQUIRED,
            'The PHAR to verify.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $phar = new Box($input->getArgument('phar'));
        } catch (UnexpectedValueException $exception) {
            $output->writeln('<error>The PHAR failed verification.</error>');

            if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity()) {
                throw $exception;
            }
        }

        if (false === isset($exception)) {
            $signature = $phar->getSignature();

            unset($phar);

            $output->writeln(sprintf(
                '<info>The PHAR was verified using %s.</info>',
                $signature['hash_type']
            ));
        }
    }
}

