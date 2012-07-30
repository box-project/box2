<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Command\Edit;

use InvalidArgumentException;
use KevinGH\Box\Box;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

/**
 * The command that removes a file in an existing PHAR.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Remove extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('edit:remove')
             ->setDescription('Removes a file from the PHAR.');

        $this->addArgument(
            'phar',
            InputArgument::REQUIRED,
            'The PHAR file to edit.'
        );

        $this->addArgument(
            'relative',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'The relative path inside the PHAR.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (true == ini_get('phar.readonly')) {
            throw new RuntimeException('PHAR writing has been disabled by "phar.readonly".');
        }

        if (false === file_exists($phar = $input->getArgument('phar'))) {
            $output->writeln(sprintf(
                '<error>The PHAR file "%s" does not exist.</error>',
                $phar
            ));

            return 1;
        }

        try {
            $box = new Box($phar);
        } catch (UnexpectedValueException $exception) {
            $output->writeln(sprintf(
                "<error>The PHAR \"%s\" could not be opened.</error>\n",
                $phar
            ));

            throw $exception;
        }

        foreach ($input->getArgument('relative') as $relative) {
            $box->delete($relative);
        }
    }
}