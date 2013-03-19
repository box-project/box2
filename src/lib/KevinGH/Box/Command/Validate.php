<?php

namespace KevinGH\Box\Command;

use Exception;
use Herrera\Json\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Validates the configuration file.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Validate extends Command
{
    /**
     * @override
     */
    protected function configure()
    {
        $this->setName('validate');
        $this->setDescription('Validates the configuration file.');
        $this->addArgument(
            'file',
            InputArgument::OPTIONAL,
            'The configuration file. (default: box.json, box.json.dist)'
        );
    }

    /**
     * @override
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verbose = (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity());

        if ($verbose) {
            $output->writeln('Validating the Box configuration file...');
        }

        if (null === ($file = $input->getArgument('file'))) {
            $file = 'box.json';

            if (false === file_exists($file)) {
                $file = 'box.json.dist';

                if (false === file_exists($file)) {
                    $output->writeln(
                        '<error>No configuration file could be found.</error>'
                    );

                    return 1;
                }
            }
        }

        if ($verbose) {
            $output->writeln("Found: $file");
        }

        $json = new Json();

        try {
            $json->validate(
                $json->decodeFile(BOX_SCHEMA_FILE),
                $json->decodeFile($file)
            );

            $output->writeln('The configuration file passed validation.');
        } catch (Exception $exception) {
            $output->writeln(
                '<error>The configuration file failed validation.</error>'
            );

            if ($verbose) {
                throw $exception;
            }

            return 1;
        }
    }
}