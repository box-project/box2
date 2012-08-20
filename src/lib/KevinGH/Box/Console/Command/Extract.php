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
 * Extracts one or more files from a PHAR.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Extract extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('extract');
        $this->setDescription('Extracts an existing PHAR.');

        $this->addArgument(
            'phar',
            InputArgument::REQUIRED,
            'The PHAR to extract.'
        );

        $this->addOption(
            'out',
            'o',
            InputOption::VALUE_REQUIRED,
            'The alternative output directory.'
        );

        $this->addOption(
            'want',
            'w',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'The file or directory you want.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === is_file($file = $input->getArgument('phar'))) {
            $output->writeln(sprintf(
                '<error>The path "%s" is not a file or does not exist.</error>',
                $file
            ));

            return 1;
        }

        $box = $this->getHelper('box');
        $file = $input->getArgument('phar');
        $phar = new Box($file);
        $target = $input->getOption('out') ?: $file . '-contents';

        $box->setOutput($output);
        $box->putln('EXTRACT', "Extracting: <comment>$file</comment>", true);

        if ($want = $input->getOption('want')) {
            foreach ($want as $item) {
                $phar->extractTo($target, $item, true);
            }
        } else {
            $phar->extractTo($target, null, true);
        }

        $box->putln('EXTRACT', '<info>Done!</info>', true);
    }
}

