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

/**
 * Removes one or more files in an existing PHAR.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Remove extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('remove');
        $this->setDescription('Remove file(s) an existing PHAR.');

        $this->addArgument(
            'phar',
            InputArgument::REQUIRED,
            'The PHAR to modify.'
        );

        $this->addArgument(
            'internal',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'The path inside the PHAR.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $phar = new Box($input->getArgument('phar'));
        $box = $this->getHelper('box');

        foreach ($input->getArgument('internal') as $relative) {
            $phar->delete($relative);
        }

        unset($phar);

        if ($box->isVerbose($output)) {
            $box->setOutput($output);
            $box->putln('REMOVE', '<info>Successfully removed the file(s)!</info>', true);
        }
    }
}

