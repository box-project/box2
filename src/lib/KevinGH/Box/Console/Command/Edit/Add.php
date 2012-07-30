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
 * The command that adds or replaces a file in an existing PHAR.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Add extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('edit:add')
             ->setDescription('Adds or replaces a file in an existing PHAR.');

        $this->addOption(
            'bin',
            'b',
            InputOption::VALUE_NONE,
            'Indicates that this is a binary file.'
        );

        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'The configuration file path.'
        );

        $this->addOption(
            'stub',
            's',
            InputOption::VALUE_NONE,
            'Indicates that this is a stub file.'
        );

        $this->addArgument(
            'phar',
            InputArgument::REQUIRED,
            'The PHAR file to edit.'
        );

        $this->addArgument(
            'file',
            InputArgument::REQUIRED,
            'The file to add or replace with.'
        );

        $this->addArgument(
            'relative',
            InputArgument::OPTIONAL,
            'The relative path to use inside the PHAR.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (true == ini_get('phar.readonly')) {
            throw new RuntimeException('PHAR writing has been disabled by "phar.readonly".');
        }

        $bin = $input->getOption('bin');
        $stub = $input->getOption('stub');

        if ($bin && $stub) {
            $output->writeln('<error>You can only use one of the options, not more than one.</error>');

            return 1;
        }

        $config = $this->getHelper('config');

        $config->load($config->find($input->getOption('config')));

        if (false === file_exists($phar = $input->getArgument('phar'))) {
            $output->writeln(sprintf(
                '<error>The PHAR file "%s" does not exist.</error>',
                $phar
            ));

            return 1;
        }

        if (false === file_exists($file = $input->getArgument('file'))) {
            $output->writeln(sprintf(
                '<error>The file "%s" does not exist.</error>',
                $file
            ));

            return 1;
        }

        $relative = $input->getArgument('relative');

        if ((false === $stub) && (null === $relative)) {
            $output->writeln('<error>The relative path is required for non-stub files.</error>');

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

        if (isset($config['replacements'])) {
            $box->setReplacements($config['replacements']);
        }

        if ($bin) {
            $box->addFile($file, $relative);
        } elseif ($stub) {
            if (false === @ ($contents = file_get_contents($file))) {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The stub file "%s" could not be read: %s',
                    $file,
                    $error['message']
                ));
            }

            $box->setStub($contents);
        } else {
            $box->importFile($relative, $file);
        }
    }
}