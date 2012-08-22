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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds a new Phar, removing any previous one.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Build extends Command
{
    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('build');
        $this->setDescription('Builds a PHAR.');

        $this->addOption(
            'configuration',
            'c',
            InputOption::VALUE_REQUIRED,
            'The alternative configuration file path.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $box = $this->getHelper('box');
        $added = 0;
        $config = $box->find($input->getOption('configuration'));

        $box->setOutput($output);
        $box->putln('BUILD', 'Building PHAR...');

        $path = $config->getOutputPath();

        $box->putln('STAGE', 'Removing any old PHARs...', true);

        $box->removePhar($path);

        $phar = new Box($path, 0, $config['alias']);

        if ($config['replacements']) {
            $box->putln('SETUP', 'Setting replacements...', true);

            $phar->setReplacements($config['replacements']);
        }

        foreach ($config->getFiles() as $absolute) {
            $relative = $config->getRelativeOf($absolute);

            $box->putln('BUILD', "<comment>s</comment> $relative", true);

            $phar->importFile($absolute, $relative);

            $added++;
        }

        foreach ($config->getFiles(true) as $absolute) {
            $relative = $config->getRelativeOf($absolute);

            $box->putln('BUILD', "<comment>b</comment> $relative", true);

            $phar->importFile($absolute, $relative);

            $added++;
        }

        if ($absolute = $config->getMainPath()) {
            $relative = $config->getRelativeOf($absolute);

            $box->putln('BUILD', "<comment>m</comment> $relative", true);

            $phar->importFile($absolute, $relative, true);

            if ($config['compression']) {
                if (false === isset($phar['index.php'])) {
                    $phar->copy($relative, 'index.php');
                } else {
                    $box->putln('SETUP', '<error>Main script will not be executable</error>');
                }
            }

            $added++;
        }

        if (true === $config['stub']) {
            if ($config['intercept']) {
                $box->putln('SETUP', 'Enabling Phar::interceptFileFunc() in generated stub...', true);

                $phar->setIntercept(true);
            }

            $box->putln('SETUP', 'Using Box generated stub...', true);

            $phar->setStub($phar->createStub());
        } elseif ($config['stub']) {
            $box->putln('SETUP', 'Using custom stub...', true);

            $phar->setStubFile($config['stub']);
        } else {
            $box->putln('SETUP', 'Using Phar default stub...', true);
        }

        if ($config['metadata']) {
            $box->putln('SETUP', 'Setting metadata...', true);

            $phar->setMetadata($config['metadata']);
        }

        if ($config['compression']) {
            $box->putln('SETUP', 'Using compression...', true);

            $phar->compress($config['compression']);
        }

        if ($config['key']) {
            if (true === $config['key-pass']) {
                $dialog = $this->getHelper('dialog');

                $config['key-pass'] = trim($dialog->ask(
                    $output,
                    '<prefix>SETUP</prefix> <question>Private key phassphrase:</question> '
                ));
            }

            $phar->usePrivateKeyFile($config->getPrivateKeyPath(), $config['key-pass']);
        }

        unset($phar);

        if ($config['compression']) {
            unlink($path);
        }

        if ($config['chmod']) {
            $box->putln('SETUP', "Setting mode to <comment>{$config['chmod']}</comment>", true);

            $box->chmodPhar($path, $config['chmod']);
        }

        if ($added) {
            $box->putln('BUILD', '<info>Done!</info>');
        } else {
            $box->putln('BUILD', '<error>No files were added.</error>');

            return 1;
        }
    }
}

